<?php

namespace App\Modules\Auth\Domain\Entities;

final class AuthRefreshToken
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly int $companyId,
        private readonly string $tokenHash,
        private readonly ?string $userAgent,
        private readonly ?string $ipAddress,
        private readonly string $expiresAt,
        private readonly ?string $revokedAt,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function companyId(): int
    {
        return $this->companyId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function expiresAt(): string
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?string
    {
        return $this->revokedAt;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}
