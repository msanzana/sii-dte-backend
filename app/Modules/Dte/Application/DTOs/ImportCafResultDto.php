<?php
namespace App\Modules\Dte\Application\DTOs;
final class ImportCafResultDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly int $dateType,
        public readonly int $folioStart,
        public readonly int $folioEnd,
        public readonly ?string $authorizedAt,
        public readonly bool $isActive,
    )
    {}
}
