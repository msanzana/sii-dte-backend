<?php
namespace App\Modules\Dte\Domain\Entities;
final class DteLineItem
{
    public function __construct(
        private readonly int $lineNumber,
        private readonly ?string $itemCodeType,
        private readonly ?string $itemCode,
        private readonly string $name,
        private readonly ?string $description,
        private readonly float $quantity,
        private readonly float $unitPrice,
        private readonly float $discountPercent,
        private readonly float $discountAmount,
        private readonly bool $taxExempt,
        private readonly float $lineAmount,
        private readonly ?array $extraPayload = null,
    )
    {}
    public function lineNumber(): int
    {
        return $this->lineNumber;
    }
    public function itemCodeType(): ?string
    {
        return $this->itemCodeType;
    }
    public function itemCode(): ?string
    {
        return $this->itemCode;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function description(): ?string
    {
        return $this->description;
    }
    public function quantity(): int
    {
        return $this->quantity;
    }
    public function unitPrice(): float
    {
        return $this->unitPrice;
    }
    public function discountPercent(): float
    {
        return $this->discountPercent;
    }
    public function discountAmount(): float
    {
        return $this->discountAmount;
    }
    public function taxExempt(): bool
    {
        return $this->taxExempt;
    }
    public function lineAmount(): float
    {
        return $this->lineAmount;
    }
    public function extraPayload(): ?array
    {
        return $this->extraPayload;
    }

}
