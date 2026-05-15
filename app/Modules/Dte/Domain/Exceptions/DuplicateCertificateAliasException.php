<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class DuplicateCertificateException extends DomainException
{
    public static function withAlias(string $alias): self
    {
         return new self("Ya existe un certificado con el alias '{$alias}' para esa empresa.");
    }
}
