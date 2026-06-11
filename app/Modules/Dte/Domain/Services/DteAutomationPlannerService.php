<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;

final class DteAutomationPlannerService
{
    public function resolveNextAction(DteDocument $document): ? string
    {
        return match($document->status()){
            DteStatus::READY_FOR_XML->value => 'prepare_for_xml',
            DteStatus::FOLIO_ASSIGNED->value => 'build_xml',
            DteStatus::XML_BUILT->value => 'build_ted',
            DteStatus::TED_BUILT->value => 'sign_xml',
            DteStatus::SIGNED->value => $document->dteType()->isFacturaFamily()
                ? 'send_factura'
                : 'send_boleta',
            default => null,
        };
    }

    public function shouldRequeueImmediately(?string $action, string $currentStatus): bool
    {
        if($action === null)
        {
            return false;
        }

        if(!in_array($action, [
            'prepare_for_xml',
            'build_xml',
            'build_ted',
            'sign_xml',
        ], true))
        {
            return false;
        }

        return in_array($currentStatus,[
            DteStatus::FOLIO_ASSIGNED->value,
            DteStatus::XML_BUILT->value,
            DteStatus::TED_BUILT->value,
            DteStatus::SIGNED->value,
        ], true);
    }
}
