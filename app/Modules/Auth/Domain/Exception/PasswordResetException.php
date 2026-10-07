<?php

namespace App\Modules\Auth\Domain\Exception;

use RuntimeException;

class PasswordResetException extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
