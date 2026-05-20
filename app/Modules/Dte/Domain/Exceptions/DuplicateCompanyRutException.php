<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class DuplicateCompanyRutException extends DomainException
{
    public static function withRut(string $rut): self
    {
        return new self("Ya existe una empresa registrada con el RUT '($rut)'.");
    }
}
