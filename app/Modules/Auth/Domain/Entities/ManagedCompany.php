<?php

namespace App\Modules\Auth\Domain\Entities;

final class ManagedCompany
{
    public function __construct(
        private readonly int $id,
        private readonly string $rut,
        private readonly string $legalName,
        private readonly bool $isActive,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function rut(): string
    {
        return $this->rut;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
