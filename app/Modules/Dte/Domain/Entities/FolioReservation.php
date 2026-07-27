<?php
namespace App\Modules\Dte\Domain\Entities;

final class FolioReservation
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $cafId,
        private readonly int $externalSystemId,
        private readonly int $companyId,
        private readonly string $siiDocumentTypeCode,
        private readonly ?int $branchOfficeNumber,
        private readonly ?int $facilityNumber,
        private readonly ?string $externalBranchCode,
        private readonly int $folioRangeFrom,
        private readonly int $folioRangeTo,
        private readonly ?int $currentFolio,
        private readonly int $reservedQuantity,
        private readonly string $reservedAt,
        private readonly ?string $expiresAt,
        private readonly bool $isCurrentlyValid,
        private readonly bool $isActive,

    ) {}
    public function id(): ?int
    {
        return $this->id;
    }
    public function cafId(): ?int
    {
        return $this->cafId;
    }
    public function externalSystemId(): ?int
    {
        return $this->externalSystemId;
    }
    public function companyId(): ?int
    {
        return $this->companyId;
    }
    public function siiDocumentTypeCode(): string
    {
        return $this->siiDocumentTypeCode;
    }
    public function branchOfficeNumber(): ?int
    {
        return $this->branchOfficeNumber;
    }
    public function facilityNumber(): ?int
    {
        return $this->facilityNumber;
    }
    public function externalBranchCode(): ?string
    {
        return $this->externalBranchCode;
    }
    public function folioRangeFrom(): ?int
    {
        return $this->folioRangeFrom;
    }
    public function folioRangeTo(): ?int
    {
        return $this->folioRangeTo;
    }
    public function currentFolio(): ?int
    {
        return $this->currentFolio;
    }
    public function reservedQuantity(): ?int
    {
        return $this->reservedQuantity;
    }
    public function reservedAt(): string
    {
        return $this->reservedAt;
    }
    public function expiresAt(): ?string
    {
        return $this->expiresAt;
    }
    public function isCurrentlyValid(): bool
    {
        return $this->isCurrentlyValid;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
