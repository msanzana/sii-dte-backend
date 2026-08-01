<?php
namespace App\Modules\Dte\Application\DTOs;
final class AssignCafRangeInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $cafId,
        public readonly int $externalSystemId,
        public readonly int $folioRangeFrom,
        public readonly int $folioRangeTo,
        public readonly ?int $branchOfficeNumber,
        public readonly ?int $facilityNumber,
        public readonly ?string $externalBranchCode,
        public readonly ?string $expiresAt,
        public readonly ?int $userId,
    ){}
}