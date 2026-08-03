<?php
namespace App\Modules\Dte\Application\Mappers;

use App\Modules\Dte\Application\DTOs\FolioDetailItemDto;
use App\Modules\Dte\Domain\Entities\FolioDetail;

final class FolioDetailApplicationMapper
{
    public function toItemDto(
        FolioDetail $detail
    ): FolioDetailItemDto
    {
        return new FolioDetailItemDto(
            id:(int) $detail->id(),
            reservationId: $detail->folioReservationId(),
            cafId: $detail->cafId(),
            externalSystemId: $detail->externalSystemId(),
            folioNumber: $detail->folioNumber(),
            folioStatusId: $detail->folioStatusId(),
            statusCode: $detail->folioStatusCode(),
            statusName: $detail->folioStatusName(),
            reserved: $detail->reserved(),
            reservedAt: $detail->reservedAt(),
            releasedAt: $detail->releasedAt(),
            usedAt: $detail->usedAt(),
            expiredAt: $detail->expiredAt(),
            dteDocumentId: $detail->dteDocumentId(),
            branchOfficeNumber: $detail->branchOfficeNumber(),
            facilityNumber: $detail->facilityNumber(),
            externalBranchCode: $detail->externalBranchCode(),
        );
    }
}