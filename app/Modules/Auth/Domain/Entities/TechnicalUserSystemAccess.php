<?php

namespace App\Modules\Auth\Domain\Entities;

final class TechnicalUserSystemAccess
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly int $companyId,
        private readonly int $externalSystemId,
        private readonly bool $isActive,
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

    public function externalSystemId(): int
    {
        return $this->externalSystemId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
