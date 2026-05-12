<?php
namespace App\Modules\Dte\Domain\Entities;
final class Country
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $isoCode2 = null,
        private readonly ?string $isoCode3 = null,
        private readonly ?string $phonCode = null,
        private readonly bool $isActive = true,
    )
    {}
    public function id():int
    {
        return $this->id;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function isoCode2(): ?string
    {
        return $this->isoCode2;
    }
    public function isoCode3(): ?string
    {
        return $this->isoCode3;
    }
    public function phonCode(): ?string
    {
        return $this->phonCode;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
}
