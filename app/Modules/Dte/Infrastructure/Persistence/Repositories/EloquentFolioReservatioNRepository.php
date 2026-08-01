<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\FolioReservation;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioReservationEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\FolioReservationPersistenceMapper;

final class EloquentFolioReservationRepository implements FolioReservationRepositoryInterface
{
    public function __construct(
        private readonly FolioReservationPersistenceMapper $mapper
    ) {}

    public function create(FolioReservation $reservation): FolioReservation
    {
        $model = FolioReservationEloquentModel::query()->create([
            'caf_id'                 => $reservation->cafId(),
            'external_system_id'     => $reservation->externalSystemId(),
            'company_id'             => $reservation->companyId(),
            'sii_document_type_code' => $reservation->siiDocumentTypeCode(),

            'branch_office_number'   => $reservation->branchOfficeNumber(),

            'facility_number'        => $reservation->facilityNumber(),

            'external_branch_code'   => $reservation->externalBranchCode(),

            'folio_range_from'       => $reservation->folioRangeFrom(),
            'folio_range_to'         => $reservation->folioRangeTo(),
            'current_folio'          => $reservation->currentFolio(),

            'reserved_quantity'      => $reservation->reservedQuantity(),
            'reserved_at'            => $reservation->reservedAt(),
            'expires_at'             => $reservation->expiresAt(),

            'is_currently_valid'     => $reservation->isCurrentlyValid(),
            'is_active'              => $reservation->isActive(),
        ]);
        return $this->mapper->toDomain($model);
    }

    public function updateCurrentFolio(int $reservationId, ?int $currentFolio): void
    {
        FolioReservationEloquentModel::query()
            ->where('id', $reservationId)
            ->update([
                'current_folio' => $currentFolio,
                'updated_at'    => now(),
            ]);
    }

    public function updateValidity(int $reservationId, bool $isCurrentlyValid, bool $isActive): void
    {
        FolioReservationEloquentModel::query()
            ->where('id', $reservationId)
            ->update([
                'is_currently_valid' => $isCurrentlyValid,
                'is_active'          => $isActive,
                'updated_at'         => now(),
            ]);
    }

    public function findByCompanyFilters(
        int $companyId,
        ?int $externalSystemId = null,
        ?string $siiDocumentTypeCode = null,
        ?int $branchOfficeNumber = null,
        ?int $facilityNumber = null
    ): array {
        $query = FolioReservationEloquentModel::query()
            ->where('company_id', $companyId);
        if ($externalSystemId !== null) {
            $query->where(
                'external_system_id',
                $externalSystemId
            );
        }
        if ($siiDocumentTypeCode !== null) {
            $query->where(
                'sii_document_type_code',
                $siiDocumentTypeCode
            );
        }
        if ($branchOfficeNumber !== null) {
            $query->where(
                'branch_office_number',
                $branchOfficeNumber
            );
        }
        if ($facilityNumber !== null) {
            $query->where(
                'facility_number',
                $facilityNumber
            );
        }
        return $query
            ->orderByDesc('is_currently_valid')
            ->orderByDesc('reserved_at')
            ->get()
            ->map(
                fn(FolioReservationEloquentModel $model) =>
                $this->mapper->toDomain($model)
            )
            ->all();
    }

    public function findById(int $reservationId): ?FolioReservation
    {
        $model = FolioReservationEloquentModel::query()
            ->find($reservationId);
        return $model
            ? $this->mapper->toDomain($model)
            : null;
    }
    public function existsOverlappingRange(
        int $cafId,
        int $folioRangeFrom,
        int $folioRangeTo
    ): bool {
        return FolioReservationEloquentModel::query()
            ->where('caf_id', $cafId)
            ->where(function ($query) use (
                $folioRangeFrom,
                $folioRangeTo
            ) {
                $query
                    ->whereBetween(
                        'folio_range_from',
                        [$folioRangeFrom, $folioRangeTo]
                    )
                    ->orWhereBetween(
                        'folio_range_to',
                        [$folioRangeFrom, $folioRangeTo]
                    )
                    ->orWhere(function ($subQuery) use (
                        $folioRangeFrom,
                        $folioRangeTo
                    ) {
                        $subQuery
                            ->where(
                                'folio_range_from',
                                '<=',
                                $folioRangeFrom
                            )
                            ->where(
                                'folio_range_to',
                                '>=',
                                $folioRangeTo
                            );
                    });
            })
            ->exists();
    }
}
