<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\ReserveFoliosInputDto;
use App\Modules\Dte\Application\DTOs\ReserveFoliosResultDto;
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
    ) {
    }

    public function execute(
        ReserveFoliosInputDto $input
    ): ReserveFoliosResultDto {
        $availableStatus = $this->folioStatusRepository
            ->findByCode('available');

        $reservedStatus = $this->folioStatusRepository
            ->findByCode('reserved');

        if (!$availableStatus || !$reservedStatus) {
            throw new RuntimeException(
                'No fue posible resolver los estados base de folio.'
            );
        }

        return DB::transaction(function () use (
            $input,
            $availableStatus,
            $reservedStatus
        ) {
            $availableFolios = $this->folioDetailRepository
                ->findAvailableByFilters(
                    companyId: $input->companyId,
                    externalSystemId: $input->externalSystemId,
                    siiDocumentTypeCode: $input->siiDocumentTypeCode,
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    externalBranchCode: $input->externalBranchCode,
                    limit: $input->requestedQuantity
                );

            if ($availableFolios === []) {
                return new ReserveFoliosResultDto(
                    requestedQuantity: $input->requestedQuantity,
                    reservedQuantity: 0,
                    availableQuantityAfter: 0,
                    folios: [],
                    status: 'empty',
                    warning: 'No existen folios disponibles para los filtros solicitados.'
                );
            }

            $reservedFolios = [];
            $affectedCafIds = [];
            $now = now()->format('Y-m-d H:i:s');

            foreach ($availableFolios as $folioDetail) {
                $this->folioDetailRepository
                    ->updateReservationState(
                        folioDetailId: (int) $folioDetail->id(),
                        folioStatusId: (int) $reservedStatus->id(),
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
                    fromStatusCode: $availableStatus->code(),
                    toStatusCode: $reservedStatus->code(),
                    message: 'Folio reservado por solicitud API.',
                    userId: null,
                    payloadJson: json_encode([
                        'sii_document_type_code' =>
                            $input->siiDocumentTypeCode,

                        'external_branch_code' =>
                            $input->externalBranchCode,
                    ], JSON_UNESCAPED_UNICODE)
                );

                $reservedFolios[] = $folioDetail->folioNumber();

                $affectedCafIds[$folioDetail->cafId()] = true;
            }

            foreach (array_keys($affectedCafIds) as $cafId) {
                $this->recalculateCafCountersService->execute(
                    (int) $cafId
                );
            }

            $availableAfter = $this->folioDetailRepository
                ->countAvailableByFilter(
                    companyId: $input->companyId,
                    externalSystemId: $input->externalSystemId,
                    siiDocumentTypeCode: $input->siiDocumentTypeCode,
                    branchOfficeNumber: $input->branchOfficeNumber,
                    facilityNumber: $input->facilityNumber,
                    externalBranchCode: $input->externalBranchCode
                );

            $reservedQuantity = count($reservedFolios);

            if ($reservedQuantity < $input->requestedQuantity) {
                return new ReserveFoliosResultDto(
                    requestedQuantity: $input->requestedQuantity,
                    reservedQuantity: $reservedQuantity,
                    availableQuantityAfter: $availableAfter,
                    folios: $reservedFolios,
                    status: 'partial',
                    warning: 'No existen folios suficientes. Se reservaron solamente los disponibles.'
                );
            }

            return new ReserveFoliosResultDto(
                requestedQuantity: $input->requestedQuantity,
                reservedQuantity: $reservedQuantity,
                availableQuantityAfter: $availableAfter,
                folios: $reservedFolios,
                status: 'success',
                warning: null
            );
        });
    }
}