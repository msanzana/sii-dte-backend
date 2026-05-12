<?php
namespace App\Modules\Dte\Domain\Entities;
final class City
{
    public function __construct(
        private readonly int $id,
        private readonly int $comuneId,
        private readonly string $name,
        private readonly ?string $code = null,
        private readonly bool $isActive = true,
    )
    {}
    public function id():int
    {
        return $this->id;
    }
    public function comuneId(): int
    {
        return $this->comuneId;
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
