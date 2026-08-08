<?php
namespace App\Modules\Dte\Application\DTOs;
final class FolioReservationItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $cafId,
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly string $siiDocumentTypeCode,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
        public readonly int $folioRangeFrom,
        public readonly int $folioRangeTo,
        public readonly ?int $currentFolio,
        public readonly int $assignedQuantity,
        public readonly string $reservedAt,
        public readonly ?string $expiresAt,
        public readonly bool $isCurrentlyValid,
        public readonly bool $isActive,
        public readonly ?string $deactivatedAt,
        public readonly ?int $deactivatedByUserId,
        public readonly ?string $deactivationSource,
        public readonly ?string $deactivationReason,

    ) {}
}
