<?php
namespace App\Modules\Dte\Domain\Exceptions;
class CafRangeOverlapException extends DomainException
{
    public static function forRange(int $dteType, int $start, int $end): self
    {
        return new self(
            "Ya existe un CAF activo solapado para el tipo {$dteType} en el rango {$start}-{$end} para este emisor."
        );
    }
}
