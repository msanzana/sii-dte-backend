<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\DeactivateFolioReservationInputDto;
use App\Modules\Dte\Application\Services\DeactivateFolioReservationService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use Throwable;

final class SyncExpiredFolioReservationsService
{
    public function __construct(
        private readonly FolioReservationRepositoryInterface $reservationRepository,
        private readonly DeactivateFolioReservationService $deactivationService,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ){}

    public function execute(int $limit = 100): int
    {
        $references = $this->reservationRepository->findExpiredActiveReferences(
            expiresBefore: now()->formate('Y-m-d H:i:s'),
            limit: $limit
        );

        $processed = 0;

        foreach($references as $reference)
        {
            try {
                $this->deactivationService->execute(
                    new DeactivateFolioReservationInputDto(
                        companyId: (int) $reference['company_id'],
                        reservationId: (int) $reference['reservation_id'],
                        userId: null,
                        reason: 'La vigencia de la asignacion de rango expiró automáticamente',
                        source: DeactivateFolioReservationService::SOURCE_AUTOMATIC_EXPIRATION
                    )
                );
                $processed++;
            } catch (Throwable $exception ) {
                $this->logRepository->error(
                    channel: 'caf:range_expiration',
                    message: 'Falló la expiración de una asignación de rango.',
                    context: [
                        'reservation_id' => $reference['id'],
                        'exception' => $exception->getMessage(),
                    ],
                    companyId : (int) $reference['company_id'],
                    code: 'CAF_RANGE_EXPIRATION_FAILED'
                );
            }
        }
        return $processed;
    }
}
