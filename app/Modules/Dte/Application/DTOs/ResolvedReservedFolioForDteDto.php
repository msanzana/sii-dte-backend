<?php
namespace App\Modules\Dte\Application\DTOs;

final class ResolvedReservedFolioForDteDto
{
    public function __construct(
        public readonly int $folioDetailId,
        public readonly int $folioReservationId,
        public readonly int $cafId,
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly string $siiDocumentTypeCode,
        public readonly int $folioNumber,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
    ){}
}
