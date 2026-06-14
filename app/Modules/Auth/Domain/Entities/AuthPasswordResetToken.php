<?php

namespace App\Modules\Auth\Domain\Entities;

final class AuthPasswordResetToken
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $tokenHash,
        private readonly string $expiresAt,
        private readonly ?string $usedAt,
        private readonly ?string $ipAddress,
        private readonly ?string $userAgent,
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

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): string
    {
        return $this->expiresAt;
    }

    public function usedAt(): ?string
    {
        return $this->usedAt;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
