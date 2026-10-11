<?php
namespace App\Modules\Dte\Domain\Entities;
use App\Modules\Dte\Domain\ValueObjects\CompanyLogoFile;
final class Company
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $rut,
        private readonly string $rutBody,
        private readonly string $rutDv,
        private readonly string $legalName,
        private readonly ?string $tradeName = null,
        private readonly ?string $giro = null,
        private readonly ?string $siiActivityCode = null,
        private readonly string $address,
        private readonly int $cityId,
        private readonly ?string $dteEmail = null,
        private readonly ?string $resolutionNumber = null,
        private readonly ?string $resolutionDate = null,
        private readonly string $siiEnvironment,
        private readonly bool $isActive = true,
        private readonly ?string $siiOfficeName = null,
        private readonly ?string $logoPath = null,
        private readonly ?string $logoMimeType = null,
        private readonly ?string $logoSha256 = null,
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
    public function siiActivityCode(): ?string
    {
        return $this->siiActivityCode;
    }
    public function siiOfficeName(): ?string
    {
        return $this->siiOfficeName;
    }
    public function logoPath(): ?string
    {
        return $this->logoPath;
    }

    public function logoMimeType(): ?string
    {
        return $this->logoMimeType;
    }

    public function logoSha256(): ?string
    {
        return $this->logoSha256;
    }

    public function hasLogo(): bool
    {
        return
            $this->logoPath !== null
            && trim($this->logoPath) !== ''
            && $this->logoMimeType !== null
            && trim($this->logoMimeType) !== ''
            && $this->logoSha256 !== null
            && trim($this->logoSha256) !== '';
    }
    public function withLogo(
        CompanyLogoFile $logo
    ): self {
        return new self(
            id:
                $this->id,
            rut:
                $this->rut,
            rutBody:
                $this->rutBody,
            rutDv:
                $this->rutDv,
            legalName:
                $this->legalName,
            tradeName:
                $this->tradeName,
            giro:
                $this->giro,
            siiActivityCode:
                $this->siiActivityCode,
            address:
                $this->address,
            cityId:
                $this->cityId,
            dteEmail:
                $this->dteEmail,
            resolutionNumber:
                $this->resolutionNumber,
            resolutionDate:
                $this->resolutionDate,
            siiEnvironment:
                $this->siiEnvironment,
            isActive:
                $this->isActive,
            siiOfficeName:
                $this->siiOfficeName,
            logoPath:
                $logo->path(),
            logoMimeType:
                $logo->mimeType(),
            logoSha256:
                $logo->sha256(),
        );
    }

    public function withoutLogo(): self
    {
        return new self(
            id:
                $this->id,
            rut:
                $this->rut,
            rutBody:
                $this->rutBody,
            rutDv:
                $this->rutDv,
            legalName:
                $this->legalName,
            tradeName:
                $this->tradeName,
            giro:
                $this->giro,
            siiActivityCode:
                $this->siiActivityCode,
            address:
                $this->address,
            cityId:
                $this->cityId,
            dteEmail:
                $this->dteEmail,
            resolutionNumber:
                $this->resolutionNumber,
            resolutionDate:
                $this->resolutionDate,
            siiEnvironment:
                $this->siiEnvironment,
            isActive:
                $this->isActive,
            siiOfficeName:
                $this->siiOfficeName,
            logoPath:
                null,
            logoMimeType:
                null,
            logoSha256:
                null,
        );
    }
}
