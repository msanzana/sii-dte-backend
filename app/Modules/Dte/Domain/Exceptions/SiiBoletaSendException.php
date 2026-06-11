<?php
namespace App\Modules\Dte\Domain\Exceptions;
class SiiBoletaSendException extends DomainException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
