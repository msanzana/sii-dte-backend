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
        private readonly int $folioRanbgeTo,
        private readonly ?int $currentFolio,
        private readonly int $reserverQuantity,
        private readonly string $reservedAt,
        private readonly ?string $expiredAt,
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
        return $this->folioRanbgeTo;
    }
    public function currentFolio(): ?int
    {
        return $this->currentFolio;
    }
    public function reservedQuantity(): ?int
    {
        return $this->reserverQuantity;
    }
    public function reservedAt(): string
    {
        return $this->reservedAt;
    }
    public function expiredAt(): ?string
    {
        return $this->expiredAt;
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
