<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\FolioDetail;

interface FolioDetailRepositoryInterface
{
    public function createBatch(array $details): void;
    public function findAvailableByFilters(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode,
        int $limit
    ):array;
    public function findReservedReversibleByFilters(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        int $folioNumber,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ):?FolioDetail;

    public function updateReservationState(
        int $folioDetailId,
        int $folioStatusId,
        bool $reserved,
        ?string $reservedAt,
        ?string $releasedAt,
        ?string $usedAt
    ) : void;
    public function attachDocument(
        int $foplioDetailId,
        int $dteDocumentId,
        ?string $usedAt=null): void;
    public function countAvailableByFilter(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ): int;
    public function countReservedByCafId(int $cafId): int;

    public function countUsedByCafId(int $cafId): int;

        /**
     * @return FolioDetail[]
     */
    public function findPageByReservationFilters(
        int $companyId,
        int $reservationId,
        ?string $statusCode,
        ?bool $reserved,
        ?int $folioFrom,
        ?int $folioTo,
        int $page,
        int $perPage
    ): array;

    public function countByReservationFilters(
        int $companyId,
        int $reservationId,
        ?string $statusCode,
        ?bool $reserved,
        ?int $folioFrom,
        ?int $folioTo
    ): int;

    /**
     * Devuelve solamente detalles en estado available o reserved
     * que no estén asociados ni utilizados.
     *
     * @return FolioDetail[]
     */
    public function findReversibleByReservationForUpdate(
        int $reservationId,
        int $limit
    ): array;

    /**
     * @param int[] $folioDetailIds
     */
    public function markExpiredByIds(
        array $folioDetailIds,
        int $expiredStatusId,
        string $expiredAt
    ): void;

    public function countByReservationId(
        int $reservationId
    ): int;

    public function countAvailableByCafId(
        int $cafId
    ): int;

    public function findReservedForDocumentCreationForUpdate(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        int $folioNumber,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ): ?FolioDetail;

    public function assignToDocument(
        int $folioDetailId,
        int $assignedStatusId,
        int $dteDocumentId
    ): bool;

    public function findByDocumentIdForUpdate(
        int $dteDocumentId
    ): ?FolioDetail;
}
