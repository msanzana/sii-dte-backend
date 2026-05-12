<?php
namespace App\Modules\Dte\Application\Mappers;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\Entities\DteReference;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Enums\DteType;
use App\Modules\Dte\Domain\ValueObjects\ReceiverData;
use Illuminate\Support\Str;

final class DteDocumentDtoMapper
{
    public function toDomain (
        CreateDteDocumentInputDto $dto,
        array $calculatedTotals,
    ): DteDocument
    {
        $items = [];
        foreach($dto->items as $index => $itemDto){
            $gross = round($itemDto->quantity * $itemDto->unitPrice, 2);
            $percentDiscount = round($gross * ($itemDto->discountPercent / 100),2);
            $effectiveDiscount = max($itemDto->discountAmount, $percentDiscount);
            $lineAmount = max(round($gross - $effectiveDiscount,2),0);

            $items[] = new DteLineItem(
                lineNumber : $index + 1,
                itemCodeType: $itemDto->itemCodeType,
                itemCode: $itemDto->itemCode,
                name: $itemDto->name,
                description: $itemDto->description,
                quantity: $itemDto->quantity,
                unitPrice: $itemDto->unitPrice,
                discountPercent: $itemDto->discountPercent,
                discountAmount: $effectiveDiscount,
                taxExempt: $itemDto->taxExempt,
                lineAmount: $lineAmount,
                extraPayload: $itemDto->extraPayload,
            );
        }
        $references = [];
        foreach($dto->references as $index => $referenceDto){
            $references[] = new DteReference(
                lineNumber: $index +1,
                referencedDteType: $referenceDto->referenceDteType,
                referencedFolio: $referenceDto->referencedFolio,
                referencedIssueDate: $referenceDto->referencedIssueDate,
                referencedCode: $referenceDto->referenceCode,
                reason: $referenceDto->reason,
                extraPayload: $referenceDto->extraPayload,
            );
        }
        return new DteDocument(
            id: null,
            externalId: $dto->externalId ?? (string) Str::uuid(),
            companyId: $dto->companyId,
            dteType: DteType::from($dto->dteType),
            issueDate: $dto->issueDate,
            status:DteStatus::READY_FOR_XML->value,
            receiver:new ReceiverData(
                document: $dto->receiver->document,
                name: $dto->receiver->name,
                giro: $dto->receiver->giro,
                address: $dto->receiver->address,
                cityId: $dto->receiver->cityId,
                email: $dto->receiver->email,
            ),
            netAmount: $calculatedTotals['net'],
            exemptAmount: $calculatedTotals['exempt'],
            taxAmount: $calculatedTotals['tax'],
            totalAmount: $calculatedTotals['total'],
            items: $items,
            references: $references,
            headerPayload: $dto->headerPayload,
            totalsPayload: $calculatedTotals,
            rawInput: $dto->rawInput,
        );
    }
}
