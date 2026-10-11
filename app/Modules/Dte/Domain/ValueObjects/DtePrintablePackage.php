<?php

namespace App\Modules\Dte\Domain\ValueObjects;

use App\Modules\Dte\Domain\Enums\PrintFormat;

final class DtePrintablePackage
{
    /**
     * @param DtePrintableCopy[] $copies
     */
    public function __construct(
        private readonly DtePrintableDocument $document,
        private readonly PrintFormat $format,
        private readonly DtePdf417LayoutSelection $pdf417,
        private readonly array $copies,
    ) {
        if ($this->copies === []) {
            throw new \InvalidArgumentException(
                'El paquete imprimible requiere al menos una copia.'
            );
        }

        foreach ($this->copies as $copy) {
            if (!$copy instanceof DtePrintableCopy) {
                throw new \InvalidArgumentException(
                    'Todas las copias del paquete deben ser DtePrintableCopy.'
                );
            }
        }
    }

    public function document(): DtePrintableDocument
    {
        return $this->document;
    }

    public function format(): PrintFormat
    {
        return $this->format;
    }

    public function pdf417(): DtePdf417LayoutSelection
    {
        return $this->pdf417;
    }

    /**
     * @return DtePrintableCopy[]
     */
    public function copies(): array
    {
        return $this->copies;
    }

    public function totalPhysicalCopies(): int
    {
        return array_sum(
            array_map(
                static fn (DtePrintableCopy $copy): int =>
                    $copy->quantity(),
                $this->copies
            )
        );
    }
}