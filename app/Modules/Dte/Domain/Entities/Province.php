<?php
namespace App\Modules\Dte\Domain\Entities;
final class Provinces
{
    public function __construct(
        private readonly int $id,
        private readonly int $regionId,
        private readonly string $name,
        private readonly ?string $code = null,
        private readonly bool $isActive = true,
    )

    {}
    public function id(): int
    {
        return $this->id;
    }
    public function regionId(): int
    {
        return $this->regionId;
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
