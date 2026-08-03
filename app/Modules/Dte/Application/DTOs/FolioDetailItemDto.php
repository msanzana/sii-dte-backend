<?php
namespace App\Modules\Dte\Application\DTOs;
final class FolioDetailItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $reservationId,
        public readonly int $cafId,
        public readonly int $externalSystemId,
        public readonly int $folioNumber,
        public readonly int $folioStatusId,
        public readonly ?string $statusCode,
        public readonly ?string $statusName,
        public readonly bool $reserved,
        public readonly ?string $reservedAt,
        public readonly ?string $releasedAt,
        public readonly ?string $usedAt,
        public readonly ?string $expiredAt,
        public readonly ?int $dteDocumentId,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,

    ) {}
}