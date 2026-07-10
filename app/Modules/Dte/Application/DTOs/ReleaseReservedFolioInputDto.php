<?php
namespace App\Modules\Dte\Application\DTOs;
final class ReleaseReservedFolioInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly string $siiDocumentTypeCode,
        public readonly int $folioNumber,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
        public readonly ?string $reason,
        public readonly ?int $userId
    ){}
}
