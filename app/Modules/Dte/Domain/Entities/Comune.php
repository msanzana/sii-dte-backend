<?php
namespace App\Modules\Dte\Domain\Entities;
final class Comune
{
    public function __construct(
        private readonly int $id,
        private readonly int $provinceId,
        private readonly string $name,
        private readonly ?string $code = null,
        private readonly bool $isActive = true,
    )
    {}
    public function if():int
    {
        return $this->id;
    }
    public function provinceId(): int
    {
        return $this->provinceId;
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
