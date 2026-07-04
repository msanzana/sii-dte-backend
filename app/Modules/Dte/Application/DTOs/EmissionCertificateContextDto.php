<?php
namespace App\Modules\Dte\Application\DTOs;
final class EmissionCertificateContextDto
{
    public function __construct(
        public readonly int $certificateId,
        public readonly int $companyId,
        public readonly string $alias,
        public readonly string $pfxPath,
        public readonly string $pfxPasswordDecrypted,
        public readonly string $pfxContents,
        public readonly ?string $serialNumber,
        public readonly ?string $subjectName,
        public readonly ?string $issuerName,
        public readonly ?string $validFrom,
        public readonly ?string $validTo,
        public readonly string $currentValidityStatus,
    ) {
    }
}
