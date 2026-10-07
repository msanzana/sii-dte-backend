<?php

namespace App\Modules\Dte\Domain\Exceptions;

final class DuplicateExternalIdException extends DomainException
{
    public static function inScope(
        string $externalId,
        int $companyId,
        ?int $externalSystemId
    ): self {
        $system =
            $externalSystemId !== null
                ? (string) $externalSystemId
                : 'interno';

        return new self(
            "El external_id {$externalId} ya existe "
            . "para la empresa {$companyId} "
            . "y el sistema {$system}."
        );
    }
}