<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

final class FolioReservationNotFoundException extends DomainException
{
    public static function withId(int $reservationId): self
    {
        return new self("No existe una asignación del rango {$reservationId} para la empresa seleccionada");    
    }
}
