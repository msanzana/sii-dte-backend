<?php

namespace App\Modules\Dte\Domain\Entities;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use InvalidArgumentException;

final class DtePrintCopyConfiguration
{
    public function __construct(
        private readonly ?int $id,
        private readonly PrintCopyType $type,
        private readonly string $name,
        private readonly bool $isRequired,
        private readonly int $defaultQuantity,
        private readonly bool $isActive,
        private readonly int $sortOrder,
        private readonly ?string $legend = null,
        private readonly ?array $parameters = null,
    ) {
        if ($this->defaultQuantity < 1) {
            throw new InvalidArgumentException(
                'La cantidad predeterminada de copias debe ser mayor o igual a 1.'
            );
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function type(): PrintCopyType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function defaultQuantity(): int
    {
        return $this->defaultQuantity;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function legend(): ?string
    {
        return $this->legend;
    }

    public function parameters(): ?array
    {
        return $this->parameters;
    }
}