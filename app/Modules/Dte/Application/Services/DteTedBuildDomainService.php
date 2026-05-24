<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteTedBuildDomainService
{
    public function assertCanBuildTed(DteDocument $document): void
    {
        if($document->status() !== DteStatus::XML_BUILT->value)
        {
            throw InvalidDocumentStateException::because(
                  "El documento {$document->id()} no está en estado xml_built."
            );
        }

        if($document->folio() === null)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene folio asignado."
            );
        }

        if ($document->unsignedXmlPath() === null || trim($document->unsignedXmlPath()) === "")
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene un XML base almacenado."
            );
        }

        if ($document->tedXml() !== null && trim($document->tedXml()) !== '') {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} ya tiene TED construido."
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
