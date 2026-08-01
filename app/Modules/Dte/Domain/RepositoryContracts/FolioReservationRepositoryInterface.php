<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\FolioReservation;

interface FolioReservationRepositoryInterface
{
    public function create(FolioReservation $reservation): FolioReservation;
    public function updateCurrentFolio(int $reservationId, ?int $currentFolio): void;
    public function updateValidity(int $reservationId, bool $isCurrentlyValid, bool $isActive): void;
    public function findByCompanyFilters(
        int $companyId,
        ?int $externalSystemId = null,
        ?string $siiDocumentTypeCode = null,
        ?int $branchOfficeNumber = null,
        ?int $facilityNumber = null): array;
    public function findById(int $reservationId): ?FolioReservation;
    public function existsOverlappingRange(
    int $cafId,
    int $folioRangeFrom,
    int $folioRangeTo
): bool;
}
