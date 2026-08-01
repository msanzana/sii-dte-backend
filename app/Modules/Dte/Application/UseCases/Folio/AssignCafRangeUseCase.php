<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\AssignCafRangeInputDto;
use App\Modules\Dte\Application\DTOs\AssignCafRangeResultDto;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Domain\Entities\FolioDetail;
use App\Modules\Dte\Domain\Entities\FolioReservation;
use App\Modules\Dte\Domain\Exceptions\CafRangeAssignmentException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AssignCafRangeUseCase
{
    private const INSERT_CHUNK_SIZE =500;
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly FolioReservationRepositoryInterface $folioReservationRepository,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
        private readonly FolioStatusRepositoryInterface $folioStatusRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
    ){}

    public function execute(
        AssignCafRangeInputDto $input
    ): AssignCafRangeResultDto
    {
        if(!$this->companyRepository->existsActiveById($input->companyId))
        {
            throw CompanyNotFoundException::withId($input->companyId);
        }
        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
        );
        return DB::transaction(function () use ($input) {
            $caf = $this->cafRepository->findByIdForUpdate($input->cafId);
            if(!$caf){
                throw CafRangeAssignmentException::cafNotFount($input->cafId);
            }
            if($caf->companyId() !== $input->companyId)
            {
                throw CafRangeAssignmentException::companyMismatch(
                    cafId: $input->cafId,
                    companyId: $input->companyId
                );
            }
            if(!$caf->isActive())
            {
                throw CafRangeAssignmentException::inactive($input->cafId);
            }
            if($caf->externalSystemId() === null)
            {
                if($caf->lastAssignedFolio() !== null)
                {
                    throw CafRangeAssignmentException::legacyCafAlredyUsed(
                        cafId: $caf->id(),
                        lastAssignedFolio: $caf->lastAssignedFolio()
                    );
                }
                $this->cafRepository->assignExternalSystem(
                    cafId: (int) $caf->id(),
                    externalSystemId: $input->externalSystemId
                );
            }
            elseif(
                $caf->externalSystemId() !== $input->externalSystemId
            )
            {
                throw CafRangeAssignmentException::externalSystemMismatch(
                    cafId: (int) $caf->id(),
                    externalSystemId: $input->externalSystemId
                );
            }

            if($input->folioRangeFrom <1 || $input->folioRangeTo < $input->folioRangeFrom)
            {
                throw CafRangeAssignmentException::invalidRange(
                    from: $input->folioRangeFrom,
                    to: $input->folioRangeTo
                );
            }
            if(!$caf->containsFolio($input->folioRangeFrom) || !$caf->containsFolio($input->folioRangeTo))
            {
                throw CafRangeAssignmentException::outsideCaf(
                    from:$input->folioRangeFrom,
                    to: $input->folioRangeTo,
                    cafFrom: $caf->folioStart(),
                    cafTo: $caf->folioEnd()
                );
            }
            if(
                $this->folioReservationRepository->existsOverlappingRange(
                    cafId: (int) $caf->id(),
                    folioRangeFrom: $input->folioRangeFrom,
                    folioRangeTo: $input->folioRangeTo
                )
            )
            {
                throw CafRangeAssignmentException::overlaps(
                    from: $input->folioRangeFrom,
                    to: $input->folioRangeTo,
                );
            }

            $availableStatus = $this->folioStatusRepository->findByCode('available');

            if(!$availableStatus)
            {
                throw new RuntimeException(
                    "No existe el estado de folio available."
                );
            }

            $assignedQuantity = $input->folioRangeTo - $input->folioRangeFrom + 1;

            $reservation = $this->folioReservationRepository->create(
                new FolioReservation(
                    id:null,
                    cafId: (int) $caf->id(),
                    externalSystemId: $input->externalSystemId,
                    companyId: $input->companyId,
                    siiDocumentTypeCode: (string) $caf->dteType(),
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    externalBranchCode: $input->externalBranchCode,
                    folioRangeFrom: $input->folioRangeFrom,
                    folioRangeTo: $input->folioRangeTo,
                    currentFolio: null,
                    reservedQuantity:$assignedQuantity,
                    reservedAt: now()->format('Y-m-d H:i:s'),
                    expiresAt: $input->expiresAt,
                    isCurrentlyValid: true,
                    isActive: true,
                ),
            );

            $createdDetails = 0;
            $chunk = [];

            for (
                $folio = $input->folioRangeFrom;
                $folio <= $input->folioRangeTo;
                $folio++
            ) {
                $chunk[] = new FolioDetail(
                    id: null,
                    folioReservationId: (int) $reservation->id(),
                    companyId: $input->companyId,
                    externalSystemId: $input->externalSystemId,
                    cafId: (int) $caf->id(),
                    siiDocumentTypeCode: (string) $caf->dteType(),
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    externalBranchCode: $input->externalBranchCode,
                    folioNumber: $folio,
                    folioStatusId: (int) $availableStatus->id(),
                    reserved: false,
                    reservedAt: null,
                    releasedAt: null,
                    usedAt: null,
                    dteDocumentId: null,
                );

                if (count($chunk) >= self::INSERT_CHUNK_SIZE) {
                    $this->folioDetailRepository->createBatch($chunk);

                    $createdDetails += count($chunk);
                    $chunk = [];
                }
            }

            /*
            * El remanente se inserta una sola vez,
            * después de finalizar el ciclo.
            */
            if ($chunk !== []) {
                $this->folioDetailRepository->createBatch($chunk);
                $createdDetails += count($chunk);
            }

            /*
            * Estos procesos se ejecutan una sola vez por rango,
            * no una vez por folio.
            */
            $this->recalculateCafCountersService->execute(
                (int) $caf->id()
            );

            $this->logRepository->info(
                channel: 'caf:range_allocation',
                message: 'Rango CAF asignado y detalles de folio generados.',
                context: [
                    'caf_id' => $caf->id(),
                    'company_id' => $input->companyId,
                    'external_system_id' => $input->externalSystemId,
                    'dte_type' => $caf->dteType(),
                    'branch_office_number' => $input->branchOfficeNumber,
                    'facility_number' => $input->facilityNumber,
                    'external_branch_code' => $input->externalBranchCode,
                    'folio_range_from' => $input->folioRangeFrom,
                    'folio_range_to' => $input->folioRangeTo,
                    'assigned_quantity' => $assignedQuantity,
                    'created_folio_details' => $createdDetails,
                    'user_id' => $input->userId,
                ],
                companyId: $input->companyId,
                code: 'CAF_RANGE_ALLOCATED'
            );
            return new AssignCafRangeResultDto(
                reservationId: (int) $reservation->id(),
                cafId: (int) $caf->id(),
                companyId: $input->companyId,
                externalSystemId: $input->externalSystemId,
                dteType: $caf->dteType(),
                folioRangeFrom: $input->folioRangeFrom,
                folioRangeTo: $input->folioRangeTo,
                assignedQuantity: $assignedQuantity,
                createdFolioDetails: $createdDetails,
                branchOfficeNumber: $input->branchOfficeNumber,
                facilityNumber: $input->facilityNumber,
                externalBranchCode: $input->externalBranchCode,
                expiresAt: $input->expiresAt,
                status: 'allocated',
            );
        });
    }

}
