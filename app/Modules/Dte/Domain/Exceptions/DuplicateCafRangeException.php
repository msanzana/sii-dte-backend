<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class DuplicateCafRangeException extends DomainException
{
    public static function because(int $companyId, int $dteType, int $folioStart, int $folioEnd): self
    {
        return new self(
             "Ya existe un CAF traslapado para la empresa {$companyId}, tipo {$dteType}, rango {$folioStart} - {$folioEnd}."
        );
    }
}
