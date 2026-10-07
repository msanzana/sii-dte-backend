<?php

namespace App\Modules\Auth\Domain\Entities;

final class AuthUser
{
    public const ACCOUNT_TYPE_HUMAN = 'human';
    public const ACCOUNT_TYPE_TECHNICAL = 'technical';

    public function __construct(
        private readonly int $id,
        private readonly string $fullName,
        private readonly string $email,
        private readonly string $accountType,
        private readonly ?string $loginUuid,
        private readonly string $passwordHash,
        private readonly bool $isActive,
        private readonly bool $isVisible,
        private readonly ?string $lastLoginAt,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function accountType(): string
    {
        return $this->accountType;
    }

    public function loginUuid(): ?string
    {
        return $this->loginUuid;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function lastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function isHuman(): bool
    {
        return $this->accountType === self::ACCOUNT_TYPE_HUMAN;
    }

    public function isTechnical(): bool
    {
        return $this->accountType === self::ACCOUNT_TYPE_TECHNICAL;
    }
}