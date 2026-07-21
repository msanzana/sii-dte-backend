<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\ReleaseReservedFolioInputDto;
use App\Modules\Dte\Application\DTOs\ReleaseReservedFolioResultDto;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReleaseReservedFolioService
{
    public function __construct(
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
        private readonly FolioStatusRepositoryInterface $folioStatusRepository,
        private readonly FolioDetailEventRepositoryInterface $FolioDetailEventRepository,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
    ){}

    public function execute(ReleaseReservedFolioInputDto $input): ReleaseReservedFolioResultDto
    {
        $availableStatus = $this->folioStatusRepository->findByCode('available');
        $reservedStatus = $this->folioStatusRepository->findByCode('reserved');

        return DB::transaction(function () use($input, $availableStatus,$reservedStatus ){
            $folioDetail = $this->folioDetailRepository->findReservedReversibleByFilters(
                companyId: $input->companyId,
                externalSystemId: $input->externalSystemId,
                siiDocumentTypeCode: $input->siiDocumentTypeCode,
                folioNumber: $input->folioNumber,
                branchOfficeNumber: $input->branchOfficeNumber,
                facilityNumber: $input->facilityNumber,
                externalBranchCode: $input->externalBranchCode,
            );

            if(!$folioDetail){
                throw new RuntimeException('El folio indicado no existe, no está reservado o ya no puede liberarse.');
            }

            if ($folioDetail->dteDocumentId() !== null)
            {
                throw new RuntimeException('El folio ya está asociado a un documento y no puede volver a estado disponible.');
            }

            $this->folioDetailRepository->updateReservationState(
                folioDetailId: (int) $folioDetail->id(),
                folioStatusId: $availableStatus->id(),
                reserved: false,
                reservedAt: null,
                releasedAt: now()->format('Y-m-d H:i:s'),
                usedAt: $folioDetail->usedAt()
            );

            $this->FolioDetailEventRepository->create(
                folioDetailId: (int) $folioDetail->id(),
                companyId: $input->companyId,
                externalSystemId: $input->externalSystemId,
                branchOfficeNumber: $input->branchOfficeNumber,
                facilityNumber: $input->facilityNumber,
                eventCode: 'released_by_api',
                fromStatusCode: $reservedStatus->code(),
                toStatusCode: $availableStatus->code(),
                message: $input->reason ?: 'Folio liberado por solicitud API.',
                userId: $input->userId,
                payloadJson: json_encode([
                    'sii_document_type_code' => $input->siiDocumentTypeCode,
                    'external_branch_code' => $input->siiDocumentTypeCode,
                    'reason' => $input->reason
                ],JSON_UNESCAPED_UNICODE)
            );

            $this->recalculateCafCountersService->execute($folioDetail->cafId());

            return new ReleaseReservedFolioResultDto(
                folioNumber: $folioDetail->folioNumber(),
                released: true,
                newStatus: $availableStatus->code(),
                message: 'El folio reservado fue liberado correctamente.'
            );
        });
    }
}
