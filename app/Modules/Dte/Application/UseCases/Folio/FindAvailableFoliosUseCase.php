<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\AvailableFoliosResultDto;
use App\Modules\Dte\Application\DTOs\FindAvailableFoliosInputDto;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;

final class FindAvailableFoliosUseCase
{
    public function __construct(
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
    ){}

    public function execute(FindAvailableFoliosInputDto $input, int $previewLimit = 20): AvailableFoliosResultDto
    {
        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
        );

        $availableQuantity = $this->folioDetailRepository->countAvailableByFilter(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
            siiDocumentTypeCode: $input->siiDocumentTypeCode,
            branchOfficeNumber: $input->branchOfficeNumber,
            facilityNumber: $input->facilityNumber,
            externalBranchCode: $input->externalBranchCode
        );

        $preview = $this->folioDetailRepository->findAvailableByFilters(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
            siiDocumentTypeCode: $input->siiDocumentTypeCode,
            branchOfficeNumber: $input->branchOfficeNumber,
            facilityNumber: $input->facilityNumber,
            externalBranchCode: $input->externalBranchCode,
            limit: $previewLimit,
        );

        return new AvailableFoliosResultDto(
            availableQuantity: $availableQuantity,
            foliosPreview: array_map(
                fn ($item) => $item->folioNumber(),
                $preview
            )
        );
    }
}
