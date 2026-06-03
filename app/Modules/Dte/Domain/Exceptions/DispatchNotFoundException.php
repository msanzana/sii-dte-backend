<?php
namespace App\Modules\Dte\Domain\Exceptions;
class DispatchNotFoundException extends DomainException
{
    public static function withId(int $dispatchId): self
    {
        return new self(
           "No existe un dispatch con el id {$dispatchId}."
        );
    }
}
