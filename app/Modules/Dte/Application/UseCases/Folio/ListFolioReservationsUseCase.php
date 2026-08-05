<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\PaginateResultDto;
use App\Modules\Dte\Application\Mappers\FolioReservationApplicationMapper;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;

final class ListFolioReservationsUseCase
{
    public function __construct(
        private readonly FolioReservationRepositoryInterface $repository,
        private readonly FolioReservationApplicationMapper $mapper,
    ){}

    public function execute(
        int $companyId,
        ?int $externalSystemId,
        ?string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?bool $isActive,
        ?bool $isCurrentlyValid,
        int $page,
        int $perPage
    ): PaginateResultDto
    {
        $items = $this->repository->findPageByCompanyFilters(
            companyId: $companyId,
            externalSystemId: $externalSystemId,
            siiDocumentTypeCode: $siiDocumentTypeCode,
            branchOfficeNumber: $branchOfficeNumber,
            facilityNumber: $facilityNumber,
            isActive: $isActive,
            isCurrentlyValid: $isCurrentlyValid,
            page: $page,
            perPage: $perPage
        );

        $total = $this->repository->countByCompanyFilters(
            companyId: $companyId,
            externalSystemId: $externalSystemId,
            siiDocumentTypeCode: $siiDocumentTypeCode,
            branchOfficeNumber: $branchOfficeNumber,
            facilityNumber: $facilityNumber,
            isActive: $isActive,
            isCurrentlyValid: $isCurrentlyValid
        );

        return new PaginateResultDto(
            items: array_map(
                fn ($reservation) =>
                    $this->mapper->toItemDto($reservation),
                $items
            ),
            total: $total,
            page: $page,
            perPage: $perPage,
            lastPage: max(
                1,
                (int) ceil($total / $perPage)
            )

        );

    }
}