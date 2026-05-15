<?php
namespace App\Modules\Dte\Application\DTOs;
final class ImportCafInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $originalFilename,
        public readonly string $tempFilePath,
        public readonly bool $isActive = true,
    )
    {}
}
