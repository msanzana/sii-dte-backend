<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePrintableIssuer
{
    public function __construct(
        private readonly string $rut,
        private readonly string $legalName,
        private readonly ?string $tradeName,
        private readonly ?string $giro,
        private readonly ?string $activityCode,
        private readonly string $address,
        private readonly string $city,
        private readonly string $comune,
        private readonly ?string $email,
        private readonly ?string $resolutionNumber,
        private readonly ?string $resolutionDate,
        private readonly ?string $siiOfficeName = null,
        private readonly ?string $logoDataUri = null,
    ) {}

    public function rut(): string { return $this->rut; }
    public function legalName(): string { return $this->legalName; }
    public function tradeName(): ?string { return $this->tradeName; }
    public function giro(): ?string { return $this->giro; }
    public function activityCode(): ?string { return $this->activityCode; }
    public function address(): string { return $this->address; }
    public function city(): string { return $this->city; }
    public function comune(): string { return $this->comune; }
    public function email(): ?string { return $this->email; }
    public function resolutionNumber(): ?string { return $this->resolutionNumber; }
    public function resolutionDate(): ?string { return $this->resolutionDate; }
    public function siiOfficeName(): ?string
    {
        return $this->siiOfficeName;
    }
    public function logoDataUri(): ?string
    {
        return $this->logoDataUri;
    }
}