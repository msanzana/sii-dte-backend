<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\FolioDetail;

interface FolioDetailRepositoryInterface
{
    public function createBNranch(array $details): void;
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
}
