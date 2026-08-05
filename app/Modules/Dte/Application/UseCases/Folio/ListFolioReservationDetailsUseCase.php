<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\PaginateResultDto;
use App\Modules\Dte\Application\Mappers\FolioDetailApplicationMapper;
use App\Modules\Dte\Domain\Exceptions\FolioReservationNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;

final class ListFolioReservationDetailsUseCase
{
    public function __construct(
        private readonly FolioReservationRepositoryInterface $reservationRepository,
        private readonly FolioDetailRepositoryInterface $detailRepository,
        private readonly FolioDetailApplicationMapper $mapper,
    ){}
    public function execute(
        int $companyId,
        int $reservationId,
        ?string $statusCode,
        ?bool $reserved,
        ?int $folioFrom,
        ?int $folioTo,
        int $page,
        int $perPage
    ): PaginateResultDto
    {
        $reservation = $this->reservationRepository->findByCompanyAndId(
            $companyId,
            $reservationId
        );

        if(!$reservation)
        {
            throw FolioReservationNotFoundException::withId(
                $reservationId
            );
        }

        $items = $this->detailRepository->findPageByReservationFilters(
            companyId: $companyId,
            reservationId: $reservationId,
            statusCode: $statusCode,
            reserved: $reserved,
            folioFrom: $folioFrom,
            folioTo: $folioTo,
            page: $page,
            perPage: $perPage
        );

        $total = $this->detailRepository->countByReservationFilters(
            companyId: $companyId,
            reservationId: $reservationId,
            statusCode: $statusCode,
            reserved: $reserved,
            folioFrom: $folioFrom,
            folioTo: $folioTo

        );

        return new PaginateResultDto(
            items: array_map(
                fn($detail) => $this->mapper->toItemDto($detail),
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