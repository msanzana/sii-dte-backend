<?php

namespace App\Modules\Auth\Domain\Entities;

final class AuthPermission
{
    public function __construct(
        private readonly int $id,
        private readonly string $code,
        private readonly string $name,
        private readonly string $module,
        private readonly ?string $description,
        private readonly bool $isActive,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function module(): string
    {
        return $this->module;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
