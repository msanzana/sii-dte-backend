<?php

namespace App\Modules\Dte\Domain\Entities;

use App\Modules\Dte\Domain\Enums\PrintCopyType;
use App\Modules\Dte\Domain\Enums\PrintFormat;

final class DtePrintConfiguration
{
    /**
     * @param DtePrintCopyConfiguration[] $copies
     */
    public function __construct(
        private readonly int $dteDocumentTypeId,
        private readonly int $dteType,
        private readonly string $name,
        private readonly bool $isActive,
        private readonly bool $isPrintable,
        private readonly PrintFormat $defaultFormat,
        private readonly ?array $printParameters,
        private readonly array $copies,
    ) {}

    public function dteDocumentTypeId(): int
    {
        return $this->dteDocumentTypeId;
    }

    public function dteType(): int
    {
        return $this->dteType;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isPrintable(): bool
    {
        return $this->isPrintable;
    }

    public function isRenderable(): bool
    {
        return $this->isActive && $this->isPrintable;
    }

    public function defaultFormat(): PrintFormat
    {
        return $this->defaultFormat;
    }

    public function printParameters(): ?array
    {
        return $this->printParameters;
    }

    /**
     * @return DtePrintCopyConfiguration[]
     */
    public function copies(): array
    {
        return $this->copies;
    }

    /**
     * @return DtePrintCopyConfiguration[]
     */
    public function activeCopies(): array
    {
        return array_values(
            array_filter(
                $this->copies,
                static fn (
                    DtePrintCopyConfiguration $copy
                ): bool => $copy->isActive()
            )
        );
    }

    public function hasActiveCopy(PrintCopyType $type): bool
    {
        foreach ($this->activeCopies() as $copy) {
            if ($copy->type() === $type) {
                return true;
            }
        }

        return false;
    }
}