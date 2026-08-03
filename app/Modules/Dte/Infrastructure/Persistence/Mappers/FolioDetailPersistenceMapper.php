<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\FolioDetail;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioDetailEloquentModel;

final class FolioDetailPersistenceMapper
{
    public function toDomain(
        FolioDetailEloquentModel $model
    ): FolioDetail
    {
        return new FolioDetail(
            id: (int) $model->id,
            folioReservationId: (int) $model->folio_reservation_id,
            companyId: (int) $model->company_id,
            externalSystemId: (int) $model->external_system_id,
            cafId: (int) $model->caf_id,
            siiDocumentTypeCode: (string) $model->sii_document_type_code,

            branchOfficeNumber: $model->branch_office_number !== null
                                ? (int) $model->branch_office_number
                                : null,
            facilityNumber: $model->facility_number !== null
                            ? (int) $model->facility_number
                            :null,
            externalBranchCode: $model->external_branch_code,
            folioNumber: (int) $model->folio_number,
            folioStatusId: (int) $model->folio_status_id,
            reserved: (bool) $model->reserved,

            reservedAt: $model->reserved_at?->format('Y-m-d H:i:s'),
            releasedAt: $model->released_at?->format('Y-m-d H:i:s'),
            usedAt: $model->used_at?->format('Y-m-d H:i:s'),

            dteDocumentId: $model->dte_document_id !== null
                        ?(int) $model->dte_document_id
                        :null,
            expiredAt:$model->expired_at?->format('Y-m-d H:i:s'),
            folioStatusCode:$model->relationLoaded('status')
                    ? $model->status?->code
                    : null,
            folioStatusName:$model->relationLoaded('status')
                    ? $model->status?->name
                    : null,
    );
    }
}