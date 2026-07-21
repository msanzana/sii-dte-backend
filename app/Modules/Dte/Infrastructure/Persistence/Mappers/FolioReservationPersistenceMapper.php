<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\FolioReservation;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioReservationEloquentModel;

final class FolioReservationPersistenceMapper
{
    public function toDomain(
        FolioReservationEloquentModel $model
    ):FolioReservation
    {
        return new FolioReservation(
            id:(int) $model->id,
            cafId: (int) $model->caf_id,
            externalSystemId: (int) $model->external_system_id,
            companyId: (int) $model->company_id,
            siiDocumentTypeCode: (string) $model->sii_document_type_code,

            branchOfficeNumber: $model->branch_office_number !== null
                ? (int) $model->branch_office_number
                : null,

            facilityNumber: $model->facility_number !== null
                ? (int) $model->facility_number
                : null,

            externalBranchCode: $model->external_branch_code,

            folioRangeFrom: (int) $model->folio_range_from,
            folioRangeTo: (int) $model->folio_range_to,

            currentFolio: $model->current_folio !== null
                ? (int) $model->current_folio
                : null,

            reservedQuantity: (int) $model->reserved_quantity,

            reservedAt: $model->reserved_at?->format('Y-m-d H:i:s')
                ?? '',

            expiresAt: $model->expires_at?->format('Y-m-d H:i:s'),

            isCurrentlyValid: (bool) $model->is_currently_valid,
            isActive: (bool) $model->is_active
        );
    }
}