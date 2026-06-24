<?php

namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCertificate;

interface SiiCertificateRepositoryInterface
{
    public function create(SiiCertificate $certificate): SiiCertificate;
    public function findById(int $id): ?SiiCertificate;

    public function findByCompanyId(int $companyId): array;
    public function hasDefaultForCompany(int $companyId): bool;
    public function findDefaultByCompanyId(int $companyId): ?SiiCertificate;
    public function findDefaultValidByCompanyId(int $companyId): ?SiiCertificate;
    public function clearDefaultByCompanyId(int $companyId): void;
    public function setDefaultById(int $certificateId, bool $isDefault): void;
    public function existsDuplicateByCompanyAndHashes(
        int $companyId,
        ?string $pfxSha256,
        ?string $metadataHash
    ): bool;
    public function updateValiditySnapshot(
        int $certificateId,
        string $lastValidityStatus,
        ?string $lastValidityCheckAt
    ): void;

}
