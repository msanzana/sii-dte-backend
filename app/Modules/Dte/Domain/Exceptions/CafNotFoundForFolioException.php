<?php
namespace App\Modules\Dte\Domain\Exceptions;

use App\Modules\Dte\Domain\Exceptions\DomainException;

class CafNotFoundForFolioException extends DomainException
{
    public static function forDocument(
        int $companyId,
        int $dteType,
        int $folio,
    ): self
    {
        return new self(
            "No existe un CAF activo que contenga el folio {$folio} para la empresa {$companyId} y el tipo DTE {$dteType}"
        );
    }

}
