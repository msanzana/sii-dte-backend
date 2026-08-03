<?php
namespace App\Modules\Dte\Application\Mappers;

use App\Modules\Dte\Application\DTOs\FolioReservationItemDto;
use App\Modules\Dte\Domain\Entities\FolioReservation;

final class FolioReservationApplicationMapper
{
    public function toItemDto(
        FolioReservation $reservation
    ): FolioReservationItemDto
    {
        return new FolioReservationItemDto(
            id: (int) $reservation->id(),
            cafId: $reservation->cafId(),
            companyId: $reservation->companyId(),
            externalSystemId: $reservation->externalSystemId(),
            siiDocumentTypeCode: $reservation->siiDocumentTypeCode(),
            branchOfficeNumber: $reservation->branchOfficeNumber(),
            facilityNumber: $reservation->facilityNumber(),
            externalBranchCode: $reservation->externalBranchCode(),
            folioRangeFrom: $reservation->folioRangeFrom(),
            folioRangeTo: $reservation->folioRangeTo(),
            currentFolio: $reservation->currentFolio(),
            assignedQuantity: $reservation->reservedQuantity(),
            reservedAt: $reservation->reservedAt(),
            expiresAt: $reservation->expiresAt(),
            isCurrentlyValid: $reservation->isCurrentlyValid(),
            isActive: $reservation->isActive(),
            deactivatedAt: $reservation->deactivatedAt(),
            deactivatedByUserId: $reservation->deactivatedByUserId(),
            deactivationSource: $reservation->deactivationSource(),
            deactivationReason: $reservation->deactivationReason(),
        );
    }
}