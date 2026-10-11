<?php

namespace App\Modules\Dte\Domain\ValueObjects;

use App\Modules\Dte\Domain\Enums\PrintCopyType;

final class DtePrintableCopy
{
    public function __construct(
        private readonly PrintCopyType $type,
        private readonly string $name,
        private readonly int $quantity,
        private readonly ?string $legend = null,
    ) {
        if ($this->quantity < 1) {
            throw new \InvalidArgumentException(
                'La cantidad de copias imprimibles debe ser al menos 1.'
            );
        }
    }

    public function type(): PrintCopyType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function legend(): ?string
    {
        return $this->legend;
    }
}