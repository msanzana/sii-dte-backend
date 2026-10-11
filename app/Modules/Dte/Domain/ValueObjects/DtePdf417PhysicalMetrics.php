<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePdf417PhysicalMetrics
{
    public function __construct(
        private readonly float $xDimMils,
        private readonly float $xDimMm,
        private readonly float $rowHeightMm,
        private readonly float $quietZoneMm,
        private readonly int $symbolWidthModules,
        private readonly int $symbolHeightModules,
        private readonly float $symbolWidthMm,
        private readonly float $symbolHeightMm,
        private readonly float $totalWidthMm,
        private readonly float $totalHeightMm,
        private readonly bool $withinRecommendedSymbolSize,
    ) {}

    public function xDimMils(): float
    {
        return $this->xDimMils;
    }

    public function xDimMm(): float
    {
        return $this->xDimMm;
    }

    public function rowHeightMm(): float
    {
        return $this->rowHeightMm;
    }

    public function quietZoneMm(): float
    {
        return $this->quietZoneMm;
    }

    public function symbolWidthModules(): int
    {
        return $this->symbolWidthModules;
    }

    public function symbolHeightModules(): int
    {
        return $this->symbolHeightModules;
    }

    public function symbolWidthMm(): float
    {
        return $this->symbolWidthMm;
    }

    public function symbolHeightMm(): float
    {
        return $this->symbolHeightMm;
    }

    public function totalWidthMm(): float
    {
        return $this->totalWidthMm;
    }

    public function totalHeightMm(): float
    {
        return $this->totalHeightMm;
    }

    public function withinRecommendedSymbolSize(): bool
    {
        return $this->withinRecommendedSymbolSize;
    }

    public function fitsWithinWidthMm(
        float $availableWidthMm
    ): bool {
        if ($availableWidthMm <= 0) {
            throw new \InvalidArgumentException(
                'El ancho disponible debe ser mayor que cero.'
            );
        }

        return $this->totalWidthMm
            <= $availableWidthMm;
    }
}
