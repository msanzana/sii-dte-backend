<?php
namespace App\Modules\Dte\Domain\Exceptions;
class LocationSummaryNotFoundException extends DomainException
{
    public static function withCityId(int $cityId): self
    {
        return new self(
            "No fue posible resolver la información geografica mínima para la ciudad {$cityId}."
        );
    }
}
