<?php

namespace App\Modules\Auth\Domain\Exceptions;

use RuntimeException;

class AuthEntityNotFoundException extends RuntimeException
{
    public static function for(string $entity, int $id): self
    {
        return new self("No existe {$entity} con id {$id}.");
    }
}
