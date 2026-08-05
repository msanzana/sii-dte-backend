<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\DeactivateFolioReservationInputDto;
use App\Modules\Dte\Application\DTOs\DeactivateFolioReservationResultDto;
use App\Modules\Dte\Domain\Exceptions\FolioReservationNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DeactivateFolioReservationService
{
    private const DETAIL_BATCH_SIZE = 500;
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AUTOMATIC_EXPIRATION = 'automatic_expiration';

    public function __construct(
        private readonly FolioReservationRepositoryInterface $reservationRepository,
        private readonly FolioDetailRepositoryInterface $detailRepository,
        private readonly FolioDetailEventRepositoryInterface $eventRepository,
        private readonly FolioStatusRepositoryInterface $statusRepository,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ){}

    public function execute(
        DeactivateFolioReservationInputDto $input
    ): DeactivateFolioReservationResultDto
    {
        $expiredStatus = $this->statusRepository->findByCode('expired');
        if(!$expiredStatus) {
            throw new RuntimeException('No existe el estado de folio expired.');
        }
        return DB::transaction(function () use (
            $input,
            $expiredStatus
        ){
            $reservation = $this->reservationRepository->findByCompanyAndIdForUpdate(
                companyId: $input->companyId,
                reservationId: $input->reservationId
            );

            if(!$reservation){
                throw FolioReservationNotFoundException::withId($input->reservationId);
            }

            $totalDetails = $this->detailRepository->countByReservationId($input->reservationId);
        
            if(!$reservation->isActive() && !$reservation->isCurrentlyValid()) {
                return new DeactivateFolioReservationResultDto(
                    reservationId: $input->reservationId,
                    deactivated: false,
                    expiredFolioDetails: 0,
                    unaffectedFolioDetails: $totalDetails,
                    source: $input->source,
                    message: 'La reserva de folios ya se encuentra desactivada y no es actualmente válida.'
                );
            }
            $now = now()->format('Y-m-d H:i:s');
            $expiresDetails = 0;
        
            do{
                $details = $this->detailRepository->findReversibleByReservationForUpdate(
                    reservationId: $input->reservationId,
                    limit: self::DETAIL_BATCH_SIZE
                );

                if($details === []) {
                    break;
                }

                $detailIds = [];
                $events = [];

                foreach($details as $detail)
                {
                    $detailIds = (int) $detail->id();
                    $events[] = [
                        'folio_detail_id' => (int) $detail->id(),
                        'company_id' => $detail->companyId(),
                        'external_system_id' => $detail->externalSystemId(),
                        'facility_number' => $detail->facilityNumber(),
                        'event_code' => $input->source === self::SOURCE_AUTOMATIC_EXPIRATION
                            ? 'expired_by_job'
                            : 'expired_by_range_deactivation',
                        'from_status_code' => $detail->folioStatusCode(),
                        'to_status_code' => $expiredStatus->code(),
                        'message' => $input->reason,
                        'user_id' => $input->userId,
                        'payload_json' => json_encode([
                            'reservation_id' => $input->reservationId,
                            'caf_id' => $reservation->cafId(),
                            'deactivation_source' => $input->source,
                        ]
                        ,JSON_UNESCAPED_UNICODE)
                    ];
                }

                $this->detailRepository->markExpiredByIds(
                    folioDetailIds: $detailIds,
                    expiredStatusId: $expiredStatus->id(),
                    expiredAt: $now
                );

                $this->eventRepository->createBatch($events);
                $expiredDetails += count($detailIds);
            } while (true);
            
            $this->reservationRepository->deactivate(
                reservationId: $input->reservationId,
                deactivatedAt: $now,
                deactivatedByUserId: $input->userId,
                deactivationSource: $input->source,
                deactivationReason: $input->reason
            );

            $this->recalculateCafCountersService->execute(
                $reservation->cafId()
            );

            $unaffectedDetails = max(
                0,
                $totalDetails - $expiresDetails
            );

            $this->logRepository->info(
                channel: 'caf:range_lifecycle',
                message: 'Asignación de rango CAF desactivada.',
                context: [
                    'reservation_id' => $input->reservationId,
                    'caf_id' => $reservation->cafId(),
                    'external_system_id' => $reservation->externalSystemId(),
                    'branch_office_number' => $reservation->branchOfficeNumber(),
                    'facility_number' => $reservation->facilityNumber(),
                    'source' => $input->source,
                    'reason' => $input->reason,
                    'expired_folio_details' => $expiredDetails,
                    'unaffected_folio_details' => $unaffectedDetails,
                    'user_id' => $input->userId,
                ],
                companyId: $input->companyId,
                code: 'CAF_RANGE_DEACTIVATED'
            );

            return new DeactivateFolioReservationResultDto(
                reservationId:
                        $input->reservationId,
                    deactivated: true,
                    expiredFolioDetails:
                        $expiredDetails,
                    unaffectedFolioDetails:
                        $unaffectedDetails,
                    source: $input->source,
                    message:
                        'La asignación de rango fue desactivada correctamente.'
            );
        });
    }
}