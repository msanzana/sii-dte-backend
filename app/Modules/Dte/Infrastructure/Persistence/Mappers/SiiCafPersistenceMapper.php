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
            authorizedAt: $model->authorized_at?->format('Y-m-d'),
            isActive: (bool) $model->is_active,
            externalSystemId: $model->extenal_system_id !== null ? (int) $model->external_system_id : null,
            requestedFoliosCount: (int) $model->requested_folios_count,
            availableFoliosCount: (int) $model->available_folios_count,
            reservedFoliosCount: (int) $model->reserved_folios_count,
            usedFoliosCount: (int) $model->used_folios_count,
        );
    }
}
