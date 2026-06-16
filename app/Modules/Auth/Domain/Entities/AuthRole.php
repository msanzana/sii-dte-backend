<?php

namespace App\Modules\Auth\Domain\Entities;

final class AuthRole
{
    /**
     * @param string[] $permissionCodes
     */
    public function __construct(
        private readonly int $id,
        private readonly string $code,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $isSystem,
        private readonly bool $isActive,
        private readonly array $permissionCodes = [],
        private readonly array $permissionIds = [],
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

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return string[]
     */
    public function permissionCodes(): array
    {
        return $this->permissionCodes;
    }
    public function permissionIds(): array
    {
        return $this->permissionIds;
    }
}
