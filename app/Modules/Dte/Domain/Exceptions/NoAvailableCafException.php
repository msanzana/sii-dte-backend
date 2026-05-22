<?php
namespace App\Modules\Dte\Domain\Exceptions;
class NoAvailableCafException extends DomainException
{
    public static function forCompanyAndType(int $companyId, int $dteType): self
    {
        return new self("No existe un CAF activo con folios disponibles para la empresa {$companyId} y tipo de DTE {$dteType}.");
    }
}
