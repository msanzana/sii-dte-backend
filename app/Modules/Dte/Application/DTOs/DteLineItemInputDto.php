<?php
namespace App\Modules\Dte\Application\DTOs;
final class DteLineItemInputDto
{
    public function __construct(
        public readonly ?string $itemCodeType,
        public readonly ?string $itemCode,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent,
        public readonly float $discountAmount,
        public readonly bool $taxExempt,
        public readonly ?array $extraPayload = null,

    ) {}
}
