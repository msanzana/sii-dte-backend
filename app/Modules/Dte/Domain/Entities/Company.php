<?php
namespace App\Models\Dte\Domain\Entities;
final class Company
{
    public function __construct(
        private readonly int $id,
        private readonly string $rut,
        private readonly string $rutBody,
        private readonly string $rutDv,
        private readonly string $legalName,
        private readonly ?string $tradeName = null,
        private readonly ?string $giro = null,
        private readonly string $address,
        private readonly int $cityId,
        private readonly ?string $dteEmail = null,
        private readonly ?string $resolutionNumber = null,
        private readonly ?string $resolutionDate = null,
        private readonly string $siiEnvironment,
        private readonly bool $isActive = true,
    )
    {}
    public function id(): int
    {
        return $this->id;
    }
    public function rut(): string
    {
        return $this->rut;
    }
    public function rutBody(): string
    {
        return $this->rutBody;
    }
    public function rutDv(): string
    {
        return $this->rutDv;
    }
    public function legalName(): string
    {
        return $this->legalName;
    }
    public function tradeName(): ?string
    {
        return $this->tradeName;
    }
    public function giro(): ?string
    {
        return $this->giro;
    }
    public function address(): string
    {
        return $this->address;
    }
    public function cityId(): int
    {
        return $this->cityId;
    }
    public function dteEmail(): ?string
    {
        return $this->dteEmail;
    }
    public function resolutionNumber(): ?string
    {
        return $this->resolutionNumber;
    }
    public function resolutionDate(): ?string
    {
        return $this->resolutionDate;
    }
    public function siiEnvironment(): string
    {
        return $this->siiEnvironment;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
