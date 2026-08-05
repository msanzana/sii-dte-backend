<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\FolioReservationItemDto;
use App\Modules\Dte\Application\Mappers\FolioReservationApplicationMapper;
use App\Modules\Dte\Domain\Exceptions\FolioReservationNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\FolioReservationRepositoryInterface;

final class GetFolioReservationUseCase
{
    public function __construct(
        private readonly FolioReservationRepositoryInterface $repository,
        private readonly FolioReservationApplicationMapper $mapper,
    ){}

    public function execute(
        int $companyId,
        int $reservationId
    ): FolioReservationItemDto
    {
        $reservation = $this->repository->findByCompanyAndId(
            companyId: $companyId,
            reservationId: $reservationId
        );
        if(!$reservation) {
            throw FolioReservationNotFoundException::withId(
                $reservationId,
            );
        }
        return $this->mapper->toItemDto($reservation);
    }
}