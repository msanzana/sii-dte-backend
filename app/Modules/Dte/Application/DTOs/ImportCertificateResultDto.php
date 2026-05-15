<?php
namespace App\Modules\Dte\Application\DTOs;
final class ImportCertificateResultDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $alias,
        public readonly ?string $serialNumber,
        public readonly ?string $subjectName,
        public readonly ?string $issuerName,
        public readonly ?string $validFrom,
        public readonly ?string $validTo,
        public readonly bool $isDefault,
        public readonly bool $isActive,
    )
    {}
}
