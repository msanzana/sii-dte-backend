<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendStatusException;

final class DteBoletaDispatchStatusDomainService
{
    public function assertCanPoll(SiiDispatch $dispatch): void
    {
        if($dispatch->transportType() !== 'rest_upload_boleta')
        {
            throw SiiBoletaSendStatusException::because(
                "El dispatch {$dispatch->id()} no corresponde a un envío REST de boleta."
            );
        }

        if($dispatch->trackId() === null || trim($dispatch->trackId()) === '')
        {
            throw SiiBoletaSendStatusException::because(
                "El dispatch {$dispatch->id()} no tiene track id."
            );
        }
    }
}
