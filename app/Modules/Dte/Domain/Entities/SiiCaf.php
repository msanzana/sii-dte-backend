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
        private readonly string $privateKeyPemEncrypted,
        private readonly ?string $publicKeyPem = null,
        private readonly ?string $authorizedAt = null,
        private readonly bool $isActive = true,
        //-> Campos Nuevos 28-07-2026
        private readonly ?int $externalSystemId = null,
        private readonly int $requestedFoliosCount = 0,
        private readonly int $availableFoliosCount =0,
        private readonly int $reservedFoliosCount =0,
        private readonly int $usedFoliosCount =0,
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
    public function privateKeyPemEncrypted(): string
    {
        return $this->privateKeyPemEncrypted;
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
    public function publicKeyPem(): ?string
    {
        return $this->publicKeyPem;
    }
    public function externalSystemId():?int
    {
        return $this->externalSystemId;
    }
    public function requestedFoliosCount():int
    {
        return $this->requestedFoliosCount;
    }
    public function availableFoliosCount():int
    {
        return $this->availableFoliosCount;
    }
    public function reservedFoliosCount():int
    {
        return $this->reservedFoliosCount;
    }
    public function usedFoliosCount():int
    {
        return $this->usedFoliosCount;
    }
    public function totalAuthorizedFolios(): int
    {
        return max(
            0,
            $this->folioEnd - $this->folioStart + 1
        );
    }

    public function containsFolio(int $folio): bool
    {
        return $folio >= $this->folioStart
            && $folio <= $this->folioEnd;
    }
}
