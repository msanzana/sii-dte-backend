<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteDocumentPreparationDomainService
{
    public function assertCanPrepareForXml(DteDocument $document): void
    {
        if($document->status() !== DteStatus::READY_FOR_XML->value)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no está en estado ready_for_xml"
            );
        }

        if($document->folio() !== null)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} ya tiene folio asignado."
            );
        }

        if(count($document->items()) === 0)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene líneas de detalle."
            );
        }
    }
}
