<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\DeactivateFolioReservationInputDto;
use App\Modules\Dte\Application\DTOs\DeactivateFolioReservationResultDto;
use App\Modules\Dte\Application\Services\DeactivateFolioReservationService;

final class DeactivateFolioReservationUseCase
{
    public function __construct(
        private readonly DeactivateFolioReservationService $service
    ){}

    public function execute(
        int $companyId,
        int $reservationId,
        ?int $userId,
        string $reason
    ): DeactivateFolioReservationResultDto
    {
        return $this->service->execute(
            new DeactivateFolioReservationInputDto(
                companyId: $companyId,
                reservationId: $reservationId,
                userId: $userId,
                reason: $reason,
                source: DeactivateFolioReservationService::SOURCE_MANUAL
            )
        );
    }
}