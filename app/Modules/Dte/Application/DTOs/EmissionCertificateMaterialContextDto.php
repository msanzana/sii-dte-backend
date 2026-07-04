<?php
namespace App\Modules\Dte\Application\DTOs;

final class EmissionCertificateMaterialContextDto
{
    public function __construct(
        public readonly int $certificateId,
        public readonly int $companyId,
        public readonly string $alias,
        public readonly string $pfxPath,
        public readonly ?string $serialNumber,
        public readonly ?string $subjectName,
        public readonly ?string $issuerName,
        public readonly ?string $validFrom,
        public readonly ?string $validTo,
        public readonly string $currentValidityStatus,
        public readonly string $privateKeyPem,
        public readonly string $certificatePem,
        public readonly string $certificateBase64,
        public readonly string $modulusBase64,
        public readonly string $exponentBase64,
    ) {
    }
}
