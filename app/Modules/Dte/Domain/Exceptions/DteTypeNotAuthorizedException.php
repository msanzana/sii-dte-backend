<?php

namespace App\Modules\Dte\Domain\Exceptions;

final class DteTypeNotAuthorizedException extends DomainException
{
    public static function inactiveOrUnknown(int $dteType): self
    {
        return new self(
            "El tipo DTE {$dteType} no existe o se encuentra inactivo."
        );
    }

    public static function unsupportedByBackend(int $dteType): self
    {
        return new self(
            "El tipo DTE {$dteType} se encuentra configurado, pero todavía no está soportado técnicamente por el backend."
        );
    }

    public static function forCompany(
        int $companyId,
        int $dteType
    ): self {
        return new self(
            "El tipo DTE {$dteType} no está autorizado para la empresa {$companyId}."
        );
    }

    public static function forSystem(
        int $companyId,
        int $externalSystemId,
        int $dteType
    ): self {
        return new self(
            "El tipo DTE {$dteType} no está autorizado para la empresa {$companyId} y el sistema externo {$externalSystemId}."
        );
    }
}