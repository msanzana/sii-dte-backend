<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\SiiCaf;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiCafEloquentModel;

final class SiiCafPersistenceMapper
{
    public function toDomain(SiiCafEloquentModel $model): SiiCaf
    {
        return new SiiCaf(
            id: $model->id,
            companyId: $model->company_id,
            dteType: (int) $model->dte_type,
            folioStart: (int) $model->folio_start,
            folioEnd: (int) $model->folio_end,
            lastAssignedFolio : $model->last_assigned_folio !== null ? (int) $model->last_assigned_folio : null,
            cafXmlPath: $model->caf_xml_path,
            privateKeyPemEncrypted: $model->private_key_pem_encrypted,
            publicKeyPem: $model->public_key_pem,
            authorizedAt: $model->authorizedAt?->format('Y-m-d'),
            isActive: (bool) $model->is_active,
        );
    }
}
