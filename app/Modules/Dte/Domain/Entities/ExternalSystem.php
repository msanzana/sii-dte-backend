<?php
namespace App\Modules\Dte\Domain\Entities;

final class ExternalSystem
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $companyId,
        private readonly string $code,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $isActive,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }
    public function companyId(): ?int
    {
        return $this->companyId;
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
    public function isActive(): bool
    {
        return $this->isActive;
    }


}