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
            id: $model->id,
            externalId: $model->external_id,
            companyId: $model->company_id,
            dteType: DteType::from((int) $model->dte_type),
            issueDate: $model->issue_date->format('Y-m-d'),
            status: $model->status,
            receiver: new ReceiverData(
                document: $model->receiver_document,
                name: $model->receiver_name,
                giro: $model->receiver_giro,
                address: $model->receiver_address,
                cityId: $model->receiver_city_id,
            ),
            netAmount: (float) $model->net_amount,
            exemptAmount: (float) $model->exempt_amount,
            taxAmount: (float) $model->tax_amount,
            totalAmount: (float) $model->total_amount,
            items: $model->items->map(function($item) {
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
                    taxExempt: (float) $item->taxExcept,
                    lineAmount: (float) $item->line_amount,
                    extraPayload: $item->extra_payload,
                );
            })->all(),
            references: $model->references->map(function($reference) {
                return new DteReference(
                    lineNumber: (int) $reference->line_number,
                    referencedDteType: $reference->referenced_dte_type,
                    referencedFolio: $reference->referenced_folio,
                    referencedIssueDate: $reference->referenced_issue_date?->format('Y-m-d'),
                    referencedCode: $reference->reference_code,
                    reason: $reference->reason,
                    extraPayload: $reference->extra_payload,
                );
            })->all(),
            headerPayload: $model->header_payload,
            totalsPayload: $model->totals_payload,
            rawInput: $model->raw_input,
            folio: $model->folio,
            siiEnvironment: $model->sii_environment,
            unsignedXmlPath: $model->last_error_code,
            signedXmlPath: $model->signed_xml_path,
            tedXml: $model->ted_xml,
            lastErrorCode: $model->last_error_code,
            lastErrorMessage: $model->last_error_message,

        );
    }
}
