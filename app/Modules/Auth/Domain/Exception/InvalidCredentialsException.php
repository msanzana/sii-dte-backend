<?php
namespace App\Modules\Auth\Domain\Exception;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public static function because():self
    {
        return new self(
            'Las credenciales ingresadas no son válidas'
        );
    }
}

