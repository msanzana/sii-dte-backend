<?php
namespace App\Modules\Dte\Domain\Entities;
final class SiiCaf
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $companyId,
        private readonly int $dteType,
        private readonly int $folioStart,
        private readonly int $folioEnd,
        private readonly ?int $lastAssignedFolio,
        private readonly string $cafXmlPath,
        private readonly string $privaterKeyPemEncrypted,
        private readonly ?string $publicKeyPem = null,
        private readonly ?string $authorizedAt = null,
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
    public function dteType(): int
    {
        return $this->dteType;
    }
    public function folioStart(): int
    {
        return $this->folioStart;
    }
    public function folioEnd(): int
    {
        return $this->folioEnd;
    }
    public function cafXmlPath(): string
    {
        return $this->cafXmlPath;

    }
    public function lastAssignedFolio(): ?int
    {
        return $this->lastAssignedFolio;
    }
    public function privaterKeyPemEncrypted(): string
    {
        return $this->privaterKeyPemEncrypted;
    }
    public function getPublicKeyPem(): ?string
    {
        return $this->publicKeyPem;
    }
    public function authorizedAt(): ?string
    {
        return $this->authorizedAt;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
