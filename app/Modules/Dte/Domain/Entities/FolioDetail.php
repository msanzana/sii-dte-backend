<?php
namespace App\Modules\Dte\Domain\Entities;
final class FolioDetail
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $folioReservationId,
        private readonly int $companyId,
        private readonly int $externalSystemId,
        private readonly int $cafId,
        private readonly string $siiDocumentTypeCode,
        private readonly ?int $branchOfficeNumber,
        private readonly ?int $facilityNumber,
        private readonly ?string $externalBranchCode,
        private readonly int $folioNumber,
        private readonly int $folioStatusId,
        private readonly bool $reserved,
        private readonly ?string $reservedAt,
        private readonly ?string $releasedAt,
        private readonly ?string $usedAt,
        private readonly ?int $dteDocumentId,
    ) {}
    public function id(): ?int
    {
        return $this->id;
    }
    public function folioReservationId(): int
    {
        return $this->folioReservationId;
    }
    public function companyId(): int
    {
        return $this->companyId;
    }
    public function externalSystemId(): int
    {
        return $this->externalSystemId;
    }
    public function cafId(): int
    {
        return $this->cafId;
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
    public function folioNumber(): int
    {
        return $this->folioNumber;
    }
    public function folioStatusId(): int
    {
        return $this->folioStatusId;
    }
    public function reserved(): bool
    {
        return $this->reserved;
    }
    public function reservedAt(): ?string
    {
        return $this->reservedAt;
    }
    public function releasedAt(): ?string
    {
        return $this->releasedAt;
    } 
    public function usedAt(): ?string
    {
        return $this->usedAt;
    }
    public function dteDocumentId():?int
    {
        return $this->dteDocumentId;
    }

}
