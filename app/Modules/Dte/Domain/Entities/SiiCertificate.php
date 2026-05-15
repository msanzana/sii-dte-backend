<?php
namespace App\Modules\Dte\Domain\Entities;
final class SiiCertificate
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $companyId,
        private readonly string $alias,
        private readonly string $pfxPath,
        private readonly string $pfxPasswordEncrypted,
        private readonly ?string $serialNumber = null,
        private readonly ?string $subjectName = null,
        private readonly ?string $issueName = null,
        private readonly ?string $validFrom = null,
        private readonly ?string $validTo = null,
        private readonly bool $isDefault = false,
        private readonly bool $isActive = true,

    )
    {}

    public function id(): ?int
    {
        return $this->id;
    }
    public function companyId(): int
    {
        return $this->companyId;
    }
    public function alias(): string
    {
        return $this->alias;
    }
    public function pfxPath(): string
    {
        return $this->pfxPath;
    }
    public function pfxPasswordEncrypted(): string
    {
        return $this->pfxPasswordEncrypted;
    }
    public function serialNumber(): ?string
    {
        return $this->serialNumber;
    }
    public function subjectName(): ?string
    {
        return $this->subjectName;
    }
    public function issueName(): ?string
    {
        return $this->issueName;
    }
    public function validFrom(): ?string
    {
        return $this->validFrom;
    }
    public function validTo(): ?string
    {
        return $this->validTo;
    }
    public function isDefault(): bool
    {
        return $this->isDefault;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }

}
