<?php
namespace App\Modules\Dte\Domain\Exceptions;

use DomainException;

final class CafRangeAssignmentException extends DomainException
{
    public static function cafNotFount(int $cafId): self
    {
        return new self(
            "No existe el CAF {$cafId}."
        );
    }

    public static function companyMismatch(
        int $cafId,
        int $companyId
    ):self
    {
        return new self(
            "El CAF {$cafId} no pertenece a la empresa {$companyId}"
        );
    }

    public static function inactive(int $cafId): self
    {
        return new self(
            "El CAF {$cafId} está inactivo y no permite asignar rangos."
        );
    }

    public static function externalSystemMismatch(
        int $cafId,
        int $externalSystemId
    ):self
    {
        return new self(
            "El CAF {$cafId} no pertenece al sistema externo {$externalSystemId}."
        );
    }

    public static function legacyCafAlredyUsed(
        int $cafId,
        int $lastAssignedFolio
    ):self
    {
        return new self(
            "El CAF {$cafId} ya fue utilizado por el flujop antiguo hasta el folio {$lastAssignedFolio}."
        );
    }

    public static function invalidRange(
        int $from,
        int $to
    ): self
    {
        return new self(
            "El rango solicitado {$from}--{$to} no es válido"
        );
    }
    public static function outsideCaf(
        int $from,
        int $to,
        int $cafFrom,
        int $cafTo
    ):self
    {
        return new self(
            "el rango {$from}--{$to} esta fuéra del rango autorizado del CAF {$cafFrom}--{$cafTo}"
        );
    }

    public static function overlaps(
        int $from,
        int $to,
    ):self
    {
        return new self(
            "El rango {$from}--{$to} se solapa con otro rango ya distribuido"
        );
    }
}