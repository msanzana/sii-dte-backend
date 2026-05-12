<?php
namespace App\Modules\Dte\Domain\Entities;
final class Region
{
    public function __construct(
        private readonly int $id,
        private readonly int $countryId,
        private readonly string $name,
        private readonly ?string $code = null,
        private readonly bool $isActive = true,
    )
    {
    }
    public function id():int
    {
        return $this->id;
    }
    public function countryId(): int
    {
        return $this->countryId;
    }
    public function name(): string
    {
        return $this->name;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
