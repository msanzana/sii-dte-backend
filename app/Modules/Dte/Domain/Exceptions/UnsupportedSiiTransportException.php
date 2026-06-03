<?php
namespace App\Modules\Dte\Domain\Exceptions;

class UnsupportedSiiTransportException extends DomainException
{
    public static function forDteType(int $dteType): self
    {
        return new self(
            "El tipo DTE {$dteType} requiere una rama de transporte distinta y no está cubierta por este capitul"
        );
    }
}
