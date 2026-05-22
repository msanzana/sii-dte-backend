<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\SiiCaf;
use App\Modules\Dte\Domain\Exceptions\NoAvailableCafException;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Domain\ValueObjects\ReservedFolio;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCafEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\SiiCafPersistenceMapper;

final class EloquentSiiCafRepository implements SiiCafRepositoryInterface
{
    public function __construct(
        private readonly SiiCafPersistenceMapper $mapper,
    ) {
    }

    public function create(SiiCaf $caf): SiiCaf
    {
        $model = new SiiCafEloquentModel();

        $model->fill([
            'company_id' => $caf->companyId(),
            'dte_type' => $caf->dteType(),
            'folio_start' => $caf->folioStart(),
            'folio_end' => $caf->folioEnd(),
            'last_assigned_folio' => $caf->lastAssignedFolio(),
            'caf_xml_path' => $caf->cafXmlPath(),
            'private_key_pem_encrypted' => $caf->privateKeyPemEncrypted(),
            'public_key_pem' => $caf->publicKeyPem(),
            'authorized_at' => $caf->authorizedAt(),
            'is_active' => $caf->isActive(),
        ]);

        $model->save();

        return $this->findById((int) $model->id);
    }

    public function findById(int $id): ?SiiCaf
    {
        $model = SiiCafEloquentModel::query()->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findActiveByCompanyAndType(int $companyId, int $dteType): array
    {
        return SiiCafEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('dte_type', $dteType)
            ->where('is_active', true)
            ->orderBy('folio_start')
            ->get()
            ->map(fn (SiiCafEloquentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function existsOverlappingRange(
        int $companyId,
        int $dteType,
        int $folioStart,
        int $folioEnd
    ): bool {
        return SiiCafEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('dte_type', $dteType)
            ->where('is_active', true)
            ->where(function ($query) use ($folioStart, $folioEnd) {
                $query
                    ->whereBetween('folio_start', [$folioStart, $folioEnd])
                    ->orWhereBetween('folio_end', [$folioStart, $folioEnd])
                    ->orWhere(function ($subQuery) use ($folioStart, $folioEnd) {
                        $subQuery
                            ->where('folio_start', '<=', $folioStart)
                            ->where('folio_end', '>=', $folioEnd);
                    });
            })
            ->exists();
    }

    public function reserveNextAvailableFolio(int $companyId, int $dteType): ReservedFolio
    {
        $cafs = SiiCafEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('dte_type', $dteType)
            ->where('is_active', true)
            ->orderBy('folio_start')
            ->lockForUpdate()
            ->get();

        foreach ($cafs as $caf) {
            $folioStart = (int) $caf->folio_start;
            $folioEnd = (int) $caf->folio_end;
            $lastAssigned = $caf->last_assigned_folio !== null ? (int) $caf->last_assigned_folio : null;

            $nextFolio = $lastAssigned === null
                ? $folioStart
                : $lastAssigned + 1;

            if ($nextFolio < $folioStart) {
                $nextFolio = $folioStart;
            }

            if ($nextFolio <= $folioEnd) {
                $caf->last_assigned_folio = $nextFolio;
                $caf->save();

                return new ReservedFolio(
                    cafId: (int) $caf->id,
                    companyId: (int) $caf->company_id,
                    dteType: (int) $caf->dte_type,
                    folio: $nextFolio,
                    cafFolioStart: $folioStart,
                    cafFolioEnd: $folioEnd,
                );
            }
        }

        throw NoAvailableCafException::forCompanyAndType($companyId, $dteType);
    }
}
