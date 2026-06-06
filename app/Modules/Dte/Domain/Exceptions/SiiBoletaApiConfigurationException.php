<?php
namespace App\Modules\Dte\Domain\Exceptions;

class SiiBoletaApiConfigurationException extends DomainException
{
    public static function missing(string $key, string $environment):self
    {
        return new self(
            "Falta configuración '{$key}' para la API REST de boleta en ambiente {$environment}."
        );
    }
}
