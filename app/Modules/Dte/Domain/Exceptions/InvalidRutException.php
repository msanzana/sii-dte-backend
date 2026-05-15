<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class InvalidRutException extends DomainException
{
    public static function because(string $rut):self
    {
        return new self("El RUT '($rut)' es inválido");
    }
}
