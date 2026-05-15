<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class InvalidCertificateException extends DomainException
{
    public static function because(string $message):self
    {
        return new self($message);
    }
}
