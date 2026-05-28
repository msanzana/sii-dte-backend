<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use DomainException;

class CertificateNotFoundException extends DomainException
{
    public static function defaultFromCompany(int $companyId): self
    {
        return new self(
            "No existe un certificado digital por defecto para la empresa {$companyId}."
        );
    }
}
