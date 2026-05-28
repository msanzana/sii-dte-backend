<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCertificateEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\SiiCertificatePersistenceMapper;

final class EloquentSiiCertificateRepository implements SiiCertificateRepositoryInterface
{
    public function __construct(
        private readonly SiiCertificatePersistenceMapper $mapper,
    ) {
    }

    public function create(SiiCertificate $certificate): SiiCertificate
    {
        $model = new SiiCertificateEloquentModel();

        $model->fill([
            'company_id' => $certificate->companyId(),
            'alias' => $certificate->alias(),
            'pfx_path' => $certificate->pfxPath(),
            'pfx_password_encrypted' => $certificate->pfxPasswordEncrypted(),
            'serial_number' => $certificate->serialNumber(),
            'subject_name' => $certificate->subjectName(),
            'issuer_name' => $certificate->issuerName(),
            'valid_from' => $certificate->validFrom(),
            'valid_to' => $certificate->validTo(),
            'is_default' => $certificate->isDefault(),
            'is_active' => $certificate->isActive(),
        ]);

        $model->save();

        return $this->findById((int) $model->id);
    }

    public function findById(int $id): ?SiiCertificate
    {
        $model = SiiCertificateEloquentModel::query()->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findByCompanyId(int $companyId): array
    {
        return SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (SiiCertificateEloquentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function hasDefaultForCompany(int $companyId): bool
    {
        return SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->exists();
    }

    public function findDefaultByCompanyId(int $companyId): ?SiiCertificate
    {
        $model = SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function clearDefaultByCompanyId(int $companyId): void
    {
        SiiCertificateEloquentModel::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'updated_at' => now(),
            ]);
    }
}
