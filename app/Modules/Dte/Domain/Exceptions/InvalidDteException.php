<?php
namespace App\Modules\Dte\Domain\Exceptions;
class InvalidDteException extends DomainException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
