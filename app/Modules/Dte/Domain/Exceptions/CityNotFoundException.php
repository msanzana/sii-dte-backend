<?php
namespace App\Modules\Dte\Domain\Exceptions;
class CityNotFoundException extends DomainException
{
    public static function withId(int $cityId): self
    {
        return new self("No exisiste una ciudad con el id {$cityId} .");
    }
}
