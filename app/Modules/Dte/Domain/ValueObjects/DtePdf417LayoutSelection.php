<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePdf417LayoutSelection
{
    public function __construct(
        private readonly DtePdf417RenderResult $renderResult,
        private readonly DtePdf417PhysicalMetrics $physicalMetrics,
        private readonly float $availableWidthMm,
        private readonly int $preferredColumns,
    ) {
        if ($this->availableWidthMm <= 0) {
            throw new \InvalidArgumentException(
                'El ancho disponible debe ser mayor que cero.'
            );
        }

        if ($this->preferredColumns < 1) {
            throw new \InvalidArgumentException(
                'La cantidad preferida de columnas debe ser mayor que cero.'
            );
        }
    }

    public function renderResult(): DtePdf417RenderResult
    {
        return $this->renderResult;
    }

    public function physicalMetrics(): DtePdf417PhysicalMetrics
    {
        return $this->physicalMetrics;
    }

    public function availableWidthMm(): float
    {
        return $this->availableWidthMm;
    }

    public function preferredColumns(): int
    {
        return $this->preferredColumns;
    }

    public function selectedColumns(): int
    {
        return $this->renderResult->columns();
    }

    public function columnsWereReduced(): bool
    {
        return $this->selectedColumns()
            < $this->preferredColumns;
    }

    public function exceedsRecommendedSymbolSize(): bool
    {
        return !$this->physicalMetrics
            ->withinRecommendedSymbolSize();
    }
}