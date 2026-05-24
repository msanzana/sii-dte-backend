<?php
namespace App\Modules\Dte\Domain\Exceptions;
class InvalidTedDataException extends DomainException
{
    public static Function because(string $message):self
    {
        return new self(
            $message
        );
    }
}
