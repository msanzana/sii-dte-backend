<?php
namespace App\Modules\Dte\Application\DTOs;
final class ImportCertificateInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $alias,
        public readonly string $originalFilename,
        public readonly string $tempFilepath,
        public readonly string $pfxPassword,
        public readonly bool $isActive = true,
    ){}
}
