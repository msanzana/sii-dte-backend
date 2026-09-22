<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteXmlBuildDomainService
{
    public function assertCanBuildXml(DteDocument $document): void
    {
        /*
        |--------------------------------------------------------------------------
        | Rebuild controlado de XML durante reproceso RSC
        |--------------------------------------------------------------------------
        |
        | Permitimos reconstruir únicamente el XML base cuando:
        |
        | - el documento ya alcanzó XML_BUILT;
        | - proviene de un reproceso identificado por RSC;
        | - existe un XML base anterior;
        | - todavía NO se generó TED;
        | - todavía NO existe XML firmado.
        |
        | De esta manera podemos sustituir un XML base técnicamente incorrecto
        | sin liberar ni cambiar el folio y sin permitir regeneraciones
        | arbitrarias de documentos que ya avanzaron en el flujo.
        |
        */

        $isControlledRscRebuild =
            $document->status() === DteStatus::XML_BUILT->value
            && $document->lastErrorCode() === 'RSC'
            && $document->unsignedXmlPath() !== null
            && $document->tedXml() === null
            && $document->signedXmlPath() === null;

        /*
        |--------------------------------------------------------------------------
        | Estados autorizados
        |--------------------------------------------------------------------------
        */

        $canBuildFromNormalFlow =
            in_array(
                $document->status(),
                [
                    DteStatus::FOLIO_ASSIGNED->value,
                    DteStatus::NEEDS_RESEND->value,
                ],
                true
            );

        if (
            !$canBuildFromNormalFlow
            && !$isControlledRscRebuild
        ) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no está en un estado válido para construir XML."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Folio obligatorio
        |--------------------------------------------------------------------------
        */

        if ($document->folio() === null) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene folio asignado."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | XML base existente
        |--------------------------------------------------------------------------
        |
        | Normalmente un XML existente bloquea una segunda construcción.
        | La única excepción es el rebuild RSC controlado definido arriba.
        |
        */

        if (
            $document->unsignedXmlPath() !== null
            && !$isControlledRscRebuild
        ) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} ya tiene un XML base generado."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Líneas de detalle
        |--------------------------------------------------------------------------
        */

        if (count($document->items()) === 0) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene lineas de detalle."
            );
        }
    }
}
