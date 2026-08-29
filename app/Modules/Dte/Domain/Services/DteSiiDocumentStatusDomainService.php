<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;

final class DteSiiDocumentStatusDomainService
{
    public function assertCanQueryStatus(DteDocument $document):void
    {
        $allowedStatuses = [
            DteStatus::SENDING->value,
            DteStatus::SENT->value,
            DteStatus::ACCEPTED->value,
            DteStatus::ACCEPTED_WITH_REPAROS->value,
            DteStatus::REJECTED->value,
        ];

        if(!in_array($document->status(), $allowedStatuses,true))
        {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no está en un estado valido para consultar situación en el SII."
            );
        }

        if($document->folio() === null) {
            throw InvalidDocumentStateException::because(
                "El documento {$document->id()} no tiene folio."
            );
        }
    }
}
