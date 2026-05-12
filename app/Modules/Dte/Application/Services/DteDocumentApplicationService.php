<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\Mappers\DteDocumentDtoMapper;
use App\Modules\Dte\Domain\Entities\DteLineItem;
use App\Modules\Dte\Domain\RepositoryContracts\DteTotalsDomainService;

final class DteDocumentApplicationService
{
    public function __construct(
        private readonly DteTotalsDomainService $totalsDomainService,
        private readonly DteDocumentDtoMapper $dtoMapper,
    )
    {}
    public function buildDomainDocument(CreateDteDocumentInputDto $dto)
    {
        $items = [];

        foreach($dto->items as $index => $itemDto)
        {
            $gross = round($itemDto->quantity = $itemDto->unitPrice,2);
            $percentDiscount = round($gross * ($itemDto->discountPercent / 100),2);
            $effectiveDiscount = max($itemDto->discountAmount, $percentDiscount);
            $lineAmount = max(round($gross - $effectiveDiscount,2),2);

            $items[] = new DteLineItem(
                lineNumber: $index +1,
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
                extraPayload: $itemDto->lineAmount,
            );
        }
        $totals = $this->totalsDomainService->calculate($items);

        return $this->dtoMapper->toDomain($dto, $totals);
    }
}
