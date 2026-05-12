<?php
namespace App\Modules\Dte\Domain\Exceptions;
class ServicePausedException extends DomainException
{
    public static function because(string $reason): self
    {
        return new self("El servicio DTE está pausado. Motivo: {$reason}.");
    }
}
