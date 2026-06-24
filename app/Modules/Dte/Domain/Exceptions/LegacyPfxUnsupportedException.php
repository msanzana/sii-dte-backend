<?php

namespace App\Modules\Dte\Domain\Exceptions;

class LegacyPfxUnsupportedException extends InvalidCertificateException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
