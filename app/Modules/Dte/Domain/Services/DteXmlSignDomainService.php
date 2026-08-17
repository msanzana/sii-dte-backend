<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteXmlSignDomainService
{
    public function assertCanSignXml(DteDocument $document): void
    {
        if($document->status() !== DteStatus::TED_BUILT->value)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no está en estado ted_built."
            );
        }

        if($document->unsignedXmlPath() === null || trim($document->unsignedXmlPath()) === '')
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene XML unsigned para firmar."
            );
        }

        if($document->tedXml() === null || trim($document->tedXml()) === '')
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene TED persistido."
            );
        }

        if (
            $document->signedXmlPath() !== null
            && trim($document->signedXmlPath()) !== ''
        ) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} ya tiene un XML firmado."
            );
        }
    }
}
