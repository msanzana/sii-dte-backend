<?php
namespace App\Modules\Dte\Domain\Exceptions;
class CompanyNotFoundException extends DomainException
{
    public static function withId(int $companyId): self
    {
        return new self("No existe una empresa activa con Id {$companyId}.");
    }
}
