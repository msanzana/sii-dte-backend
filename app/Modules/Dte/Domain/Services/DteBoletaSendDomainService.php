<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\Exceptions\UnsupportedSiiTransportException;

final class DteBoletaSendDomainService
{
    public function assertCanSend(DteDocument $document): void{
        if(!$document->dteType()->isBoletaFamily())
        {
            throw UnsupportedSiiTransportException::forDteType(
                $document->dteType()->value
            );
        }

        if($document->status() !== DteStatus::SIGNED->value)
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no esta en estado signed."
            );
        }

        if($document->signedXmlPath() === null || trim($document->signedXmlPath()) === '')
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene Xml firmado para enviar."
            );
        }
    }
}
