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
    public function findByCompanyAndId(
        int $companyId,
        int $reservationId
    ): ?FolioReservation;

    public function findByCompanyAndIdForUpdate(
        int $companyId,
        int $reservationId
    ): ?FolioReservation;

    /**
     * @return FolioReservation[]
     */
    public function findPageByCompanyFilters(
        int $companyId,
        ?int $externalSystemId,
        ?string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?bool $isActive,
        ?bool $isCurrentlyValid,
        int $page,
        int $perPage
    ): array;

    public function countByCompanyFilters(
        int $companyId,
        ?int $externalSystemId,
        ?string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?bool $isActive,
        ?bool $isCurrentlyValid
    ): int;

    public function deactivate(
        int $reservationId,
        string $deactivatedAt,
        ?int $deactivatedByUserId,
        string $deactivationSource,
        string $deactivationReason
    ): void;

    /**
     * @return array<int, array{
     *     id:int,
     *     company_id:int
     * }>
     */
    public function findExpiredActiveReferences(
        string $expiresBefore,
        int $limit
    ): array;
}
