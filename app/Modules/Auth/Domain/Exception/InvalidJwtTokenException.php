<?php
namespace App\Modules\Auth\Domain\Exception;

use RuntimeException;

class InvalidJwtTokenException extends RuntimeException
{
    public static function because(string $message):self
    {
        return new self($message);
    }

}
