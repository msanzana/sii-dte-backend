<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\ReserveFoliosInputDto;
use App\Modules\Dte\Application\DTOs\ReserveFoliosResultDto;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReserveFoliosService
{
    public function __construct(
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
        private readonly FolioStatusRepositoryInterface $folioStatusRepository,
        private readonly FolioDetailEventRepositoryInterface $folioDetailEventRepository,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
    ){}

    public function execute(ReserveFoliosInputDto $input): ReserveFoliosResultDto
    {
        $availableStatus = $this->folioStatusRepository->findByCode('available');
        $reservedStatus = $this->folioStatusRepository->findByCode('reserved');

        if(!$availableStatus || !$reservedStatus)
        {
            throw new RuntimeException('No fue posible resolver los estados base del folio.');
        }

        return DB::transaction(function () use($input, $availableStatus, $reservedStatus){
            $availableFolios = $this->folioDetailRepository->findAvailableByFilters(
                companyId: $input->companyId,
                externalSystemId: $input->externalSystemId,
                siiDocumentTypeCode: $input->siiDocumentTypeCode,
                branchOfficeNumber: $input->branchOfficeNumber,
                facilityNumber: $input->facilityNumber,
                externalBranchCode: $input->externalBranchCode,
                limit: $input->requestedQuantity
            );

            $reservedFolios = [];
            $now = now()->format('Y-m-d H:i:s');

            foreach($availableFolios as $folioDetail)
            {
                $this->folioDetailRepository->updateReservationState(
                    folioDetailId: (int) $folioDetail->id(),
                    folioStatusId: $reservedStatus->id(),
                    reserved: true,
                    reservedAt: $now,
                    releasedAt: null,
                    usedAt: null
                );
                $this->folioDetailEventRepository->create(
                    folioDetailId: (int) $folioDetail->id(),
                    companyId: $input->companyId,
                    externalSystemId: $input->externalSystemId,
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    eventCode: 'reserved_by_api',
                    fromStatusCode: $reservedStatus->code(),
                    toStatusCode: $reservedStatus->code(),
                    message: 'Folio reservado por solicitud API.',
                    userId: null,
                    payloadJson: json_encode([
                        'sii_document_type_code' => $input->siiDocumentTypeCode,
                        'external_branch_code' => $input->externalBranchCode,
                    ], JSON_UNESCAPED_UNICODE)
                );
                $reservedFolios[] = $folioDetail->folioNumber();

                if($availableFolios !== [])
                {
                    $cafId = $availableFolios[0]->cafId();
                    $this->recalculateCafCountersService->execute($cafId);
                }

                $availableAfter = $this->folioDetailRepository->countAvailableByFilter(
                    companyId: $input->companyId,
                    externalSystemId: $input->externalSystemId,
                    siiDocumentTypeCode: $input->siiDocumentTypeCode,
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    externalBranchCode: $input->externalBranchCode,
                );

                $reservedQuantity = count($reservedFolios);

                if ($reservedQuantity === 0)
                {
                    return new ReserveFoliosResultDto(
                        requestedQuantity: $input->requestedQuantity,
                        reservedQuantity:0,
                        availableQuantityAfter:0,
                        folios:[],
                        status: 'empty',
                        warning: 'No existen folios disponibles para los filtros solicitados.'
                    );
                }

                if($reservedQuantity < $input->requestedQuantity)
                {
                    return new ReserveFoliosResultDto(
                        requestedQuantity: $input->requestedQuantity,
                        reservedQuantity: $reservedQuantity,
                        availableQuantityAfter: $availableAfter,
                        folios: $reservedFolios,
                        status: 'partial',
                        warning: '',
                    );
                }

                return new ReserveFoliosResultDto(
                    requestedQuantity: $input->requestedQuantity,
                    reservedQuantity: $reservedQuantity,
                    availableQuantityAfter: $availableAfter,
                    folios: $reservedFolios,
                    status: 'success',
                    warning: null,
                );
            }
        });
    }
}
