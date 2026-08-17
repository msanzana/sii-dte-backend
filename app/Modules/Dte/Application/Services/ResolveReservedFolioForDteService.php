<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\CreateDteDocumentInputDto;
use App\Modules\Dte\Application\DTOs\ResolvedReservedFolioForDteDto;
use App\Modules\Dte\Domain\Exceptions\DteReservedFolioException;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;

final class ResolveReservedFolioForDteService
{
    public function __construct(
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository
    ){}

    public function execute(
        CreateDteDocumentInputDto $input,
    ):?ResolvedReservedFolioForDteDto
    {
        /*
         * Flujo interno/legado.
         */
        if ($input->externalSystemId === null) {
            if ($input->proposedFolio !== null) {
                throw DteReservedFolioException::externalSystemRequired();
            }

            return null;
        }

        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId
        );

        if ($input->proposedFolio === null) {
            throw DteReservedFolioException::proposedFolioRequired(
                $input->externalSystemId
            );
        }
        if($input->proposedSiiDocumentType !== null &&
            $input->proposedSiiDocumentType !== $input->dteType)
        {
            throw DteReservedFolioException::documentTypeMismatch(
                documentType: $input->dteType,
                proposedType: $input->proposedSiiDocumentType

            );
        }
        $detail = $this->folioDetailRepository->findReservedForDocumentCreationForUpdate(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
            siiDocumentTypeCode: (string) $input->dteType,
            folioNumber: $input->proposedFolio,
            branchOfficeNumber: $input->branchOfficeNumber,
            facilityNumber: $input->facilityNumber,
            externalBranchCode:$input->externalBranchCode
        );

        if(!$detail)
        {
            throw DteReservedFolioException::unavailable($input->proposedFolio);
        }

        return new ResolvedReservedFolioForDteDto(
            folioDetailId: (int) $detail->id(),
            folioReservationId: $detail->folioReservationId(),
            cafId: $detail->cafId(),
            companyId: $detail->companyId(),
            externalSystemId: $detail->externalSystemId(),
            siiDocumentTypeCode: $detail->siiDocumentTypeCode(),
            folioNumber: $detail->folioNumber(),
            branchOfficeNumber: $detail->branchOfficeNumber(),
            facilityNumber: $detail->facilityNumber(),
            externalBranchCode: $detail->externalBranchCode(),
        );
    }
}