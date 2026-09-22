<?php
namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendStatusException;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
final class DteBoletaDispatchStatusDomainService
{
    public function assertCanPoll(SiiDispatch $dispatch): void
    {
        if ($dispatch->transportType() !== 'rest_upload_boleta') {
            throw SiiBoletaSendStatusException::because(
                "El dispatch {$dispatch->id()} no corresponde a un envío REST de boleta."
            );
        }

        if ($dispatch->trackId() === null || trim($dispatch->trackId()) === '') {
            throw SiiBoletaSendStatusException::because(
                "El dispatch {$dispatch->id()} no tiene track id."
            );
        }

        $terminalStatuses = [
            DispatchStatus::PROCESSED->value,
            DispatchStatus::ACCEPTED->value,
            DispatchStatus::REJECTED->value,
            DispatchStatus::FAILED->value,
            DispatchStatus::UPLOAD_REJECTED->value,
        ];

        if (
            in_array(
                $dispatch->status(),
                $terminalStatuses,
                true
            )
        ) {
            throw SiiBoletaSendStatusException::because(
                "El dispatch {$dispatch->id()} se encuentra en estado {$dispatch->status()} y no permite un nuevo polling."
            );
        }
    }
}
