<?php

namespace App\Modules\Dte\Domain\ValueObjects;

use App\Modules\Dte\Domain\Enums\PrintFormat;

final class DtePrintFormatPhysicalProfile
{
    public function __construct(
        private readonly PrintFormat $format,
        private readonly float $paperWidthMm,
        private readonly float $leftMarginMm,
        private readonly float $rightMarginMm,
    ) {
        if ($this->paperWidthMm <= 0) {
            throw new \InvalidArgumentException(
                'El ancho del papel debe ser mayor que cero.'
            );
        }

        if (
            $this->leftMarginMm < 0
            || $this->rightMarginMm < 0
        ) {
            throw new \InvalidArgumentException(
                'Los márgenes físicos no pueden ser negativos.'
            );
        }

        if (
            $this->leftMarginMm
            + $this->rightMarginMm
            >= $this->paperWidthMm
        ) {
            throw new \InvalidArgumentException(
                'Los márgenes consumen todo el ancho disponible.'
            );
        }
    }

    public function format(): PrintFormat
    {
        return $this->format;
    }

    public function paperWidthMm(): float
    {
        return $this->paperWidthMm;
    }

    public function leftMarginMm(): float
    {
        return $this->leftMarginMm;
    }

    public function rightMarginMm(): float
    {
        return $this->rightMarginMm;
    }

    public function usableWidthMm(): float
    {
        return
            $this->paperWidthMm
            - $this->leftMarginMm
            - $this->rightMarginMm;
    }
}