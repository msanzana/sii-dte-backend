<?php
namespace App\Modules\Dte\Application\DTOs;
final class AssignCafRangeResultDto
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $cafId,
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly int $dteType,
        public readonly int $folioRangeFrom,
        public readonly int $folioRangeTo,
        public readonly int $assignedQuantity,
        public readonly int $createdFolioDetails,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
        public readonly ?string $expiresAt,
        public readonly string $status,
    ){}
}