<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Entities\DteReference;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;

final class DteDocumentPersistenceMapper
{
    public function toDomain(DteDocumentEloquentModel $model): DteDocument
    {
        return new DteDocument(
        id: (int) $model->id,
        externalId: (string) $model->external_id,
        companyId: (int) $model->company_id,
        dteType: DteType::from((int) $model->dte_type),
        issueDate: $model->issue_date->format('Y-m-d'),
        status: (string) $model->status,
        receiver: new ReceiverData(
            document: $model->receiver_document,
            name: $model->receiver_name,
            giro: $model->receiver_giro,
            address: $model->receiver_address,
            cityId: $model->receiver_city_id !== null
                    ? (int) $model->receiver_city_id
                    : null,
            email: $model->receiver_email,
        ),
        netAmount: (float) $model->net_amount,
        exemptAmount: (float) $model->exempt_amount,
        taxAmount: (float) $model->tax_amount,
        totalAmount: (float) $model->total_amount,
        items: $model->items
            ->map(function ($item) {
                return new DteLineItem(
                    lineNumber: (int) $item->line_number,
                    itemCodeType: $item->item_code_type,
                    itemCode: $item->item_code,
                    name: $item->name,
                    description: $item->description,
                    quantity: (float) $item->quantity,
                    unitPrice: (float) $item->unit_price,
                    discountPercent: (float) $item->discount_percent,
                    discountAmount: (float) $item->discount_amount,
                    taxExempt: (bool) $item->tax_exempt,
                    lineAmount: (float) $item->line_amount,
                    extraPayload: $item->extra_payload,
                );
            })->all(),
            references: $model->references
                ->map(function ($reference) {
                    return new DteReference(
                        lineNumber: (int) $reference->line_number,
                        referencedDteType: $reference->referenced_dte_type,
                        referencedFolio: $reference->referenced_folio,
                        referencedIssueDate: $reference->referenced_issue_date?->format('Y-m-d'),
                        referenceCode: $reference->reference_code,
                        reason: $reference->reason,
                        extraPayload: $reference->extra_payload,
                    );
                })->all(),
            headerPayload:$model->header_payload,
            totalsPayload:$model->totals_payload,
            rawInput:$model->raw_input,
            folio: $model->folio !== null
                    ? (int) $model->folio
                    : null,
            siiEnvironment:$model->sii_environment,
            unsignedXmlPath:$model->unsigned_xml_path,
            signedXmlPath:$model->signed_xml_path,
            tedXml:$model->ted_xml,
            lastErrorCode:$model->last_error_code,
            lastErrorMessage:$model->last_error_message,
            externalSystemId:$model->external_system_id !== null
                    ? (int) $model->external_system_id
                    : null,
            cafId: $model->caf_id !== null
                    ? (int) $model->caf_id
                    : null,
            folioReservationId:$model->folio_reservation_id !== null
                    ? (int) $model->folio_reservation_id
                    : null,
            branchOfficeNumber:$model->branch_office_number !== null
                    ? (int) $model->branch_office_number
                    : null,
            facilityNumber: $model->facility_number !== null
                    ? (int) $model->facility_number
                    : null,
            externalBranchCode: $model->external_branch_code,
        );
    }
}
