<?php

namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCertificate;

interface SiiCertificateRepositoryInterface
{
    public function create(SiiCertificate $certificate): SiiCertificate;

    public function findById(int $id): ?SiiCertificate;

    /**
     * @return SiiCertificate[]
     */
    public function findByCompanyId(int $companyId): array;

    public function hasDefaultForCompany(int $companyId): bool;

    public function findDefaultByCompanyId(int $companyId): ?SiiCertificate;

    public function clearDefaultByCompanyId(int $companyId): void;
}
