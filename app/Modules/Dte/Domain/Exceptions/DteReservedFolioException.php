<?php
namespace App\Modules\Dte\Domain\Exceptions;
final class DteReservedFolioException extends DomainException
{
    public static function externalSystemRequired():self
    {
        return new self(
            'Se indicó un folio propuesto pero no se indicó external_system_id.'
        );
    }
    public static function proposedFolioRequired(
        int $externalSystemId
    ):self
    {
        return new self(
            "El sistema externo {$externalSystemId} debe indicar un folio previamente reservado."
        );
    }
    public static function documentTypeMismatch(
        int $documentType,
        int $proposedType
    ):self{
        return new self(
            "El tipo DTE {$proposedType} indicado para el folio no coincide con el tipo {$documentType} del documento."
        );
    }
    public static function unavailable(
        int $folio
    ):self
    {
        return new self(
            "El folio {$folio} no existe, o no está reservado, ya fue utilizado o no pertenece a la distribución solicitada"
        );
    }
    public static function assignmentFailed(
        int $folio
    ):self
    {
        return new self(
            "El folio {$folio} dejó de estar disponible durante la creación del documento."
        );
    }
}
