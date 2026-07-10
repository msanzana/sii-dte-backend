<?php
namespace App\Modules\Dte\Application\DTOs;

final class ReserveFoliosInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly string $siiDocumentTypeCode,
        public readonly int $requestQuantity,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
    ){}
}
