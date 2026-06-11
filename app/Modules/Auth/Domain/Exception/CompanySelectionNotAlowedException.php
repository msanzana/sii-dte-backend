<?php
namespace App\Modules\Auth\Domain\Exception;

use RuntimeException;

class CompanySelectionNotAlowedException extends RuntimeException
{
    public static function forUserAndCompany(int $userId, int $companyId):self
    {
        return new self(
            "El usuario {$userId} no esta autorizada para seleccionar la empresa {$companyId}."
        );
    }

}
