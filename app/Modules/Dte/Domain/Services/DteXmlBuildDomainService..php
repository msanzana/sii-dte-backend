<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteXmlBuildDomainService
{
    public function assertCanBuildXml(DteDocument $document): void
    {
        if($document->status() !== DteStatus::FOLIO_ASSIGNED->value)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no está en estado folio_assigned."
            );
        }

        if($document->folio() === null)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene folio asignado."
            );
        }

        if($document->unsignedXmlPath() === null)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} ya tiene un XML base generado."
            );
        }

        if(count($document->items()) === 0)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene lineas de detalle."
            );
        }
    }
}
