<?php

namespace App\Modules\Dte\Domain\Entities;

use Carbon\CarbonImmutable;

final class SiiCertificate
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $companyId,
        private readonly string $alias,
        private readonly string $pfxPath,
        private readonly string $pfxPasswordEncrypted,
        private readonly string $serialNumber,
        private readonly string $subjectName,
        private readonly string $issuerName,
        private readonly string $validFrom,
        private readonly string $validTo,
        private readonly ?string $pfxSha256 = null,
        private readonly ?string $metadataHash = null,
        private readonly ?string $certificateFingerprintSha1 = null,
        private readonly bool $hasPrivateKey = true,
        private readonly bool $isDefault = false,
        private readonly bool $isActive = true,
        private readonly ?string $lastValidityCheckAt = null,
        private readonly ?string $lastValidityStatus = null,
    ) {
    }

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

    public function pfxSha256(): ?string
    {
        return $this->pfxSha256;
    }

    public function metadataHash(): ?string
    {
        return $this->metadataHash;
    }

    public function certificateFingerprintSha1(): ?string
    {
        return $this->certificateFingerprintSha1;
    }

    public function subjectName(): string
    {
        return $this->subjectName;
    }

    public function issuerName(): string
    {
        return $this->issuerName;
    }

    public function serialNumber(): string
    {
        return $this->serialNumber;
    }

    public function validFrom(): string
    {
        return $this->validFrom;
    }

    public function validTo(): string
    {
        return $this->validTo;
    }

    public function hasPrivateKey(): bool
    {
        return $this->hasPrivateKey;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function lastValidityCheckAt(): ?string
    {
        return $this->lastValidityCheckAt;
    }

    public function lastValidityStatus(): ?string
    {
        return $this->lastValidityStatus;
    }
    public function pfxPath(): string
    {
        return $this->pfxPath;
    }
    public function pfxPasswordEncrypted(): string
    {
        return $this->pfxPasswordEncrypted;
    }

    public function isCurrentlyValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }
        if ($this->validFrom === null || $this->validTo === null) {
            return false;
        }
        $now = CarbonImmutable::now();
        $validFrom = CarbonImmutable::parse($this->validFrom);
        $validTo = CarbonImmutable::parse($this->validTo);

        return $now->betweenIncluded($validFrom, $validTo);
    }

    public function currentValidityStatus(): string
    {
        if (!$this->isActive) {
            return 'inactive';
        }
        if ($this->validFrom === null || $this->validTo === null) {
            return 'missing_validity_dates';
        }
        $now = CarbonImmutable::now();
        $validFrom = CarbonImmutable::parse($this->validFrom);
        $validTo = CarbonImmutable::parse($this->validTo);

        if ($now->lt($validFrom)) {
            return 'not_yet_valid';
        }

        if ($now->gt($validTo)) {
            return 'expired';
        }

        return 'valid';
    }
}
