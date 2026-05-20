<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCertificateEloquentModel;

final class SiiCertificatePersistenceMapper
{
    public function toDomain(SiiCertificateEloquentModel $model): SiiCertificate
    {
        return new SiiCertificate(
            id: $model->id,
            companyId: $model->company_id,
            alias: $model->alias,
            pfxPath: $model->pfx_path,
            pfxPasswordEncrypted: $model->pfx_password_encrypted,
            serialNumber: $model->serial_number,
            subjectName: $model->subject_name,
            issuerName: $model->issuer_name,
            validFrom: $model->valid_from?->format('Y-m-d H:i:s'),
            validTo: $model->valid_to?->format('Y-m-d H:i:s'),
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
        );
    }
}
