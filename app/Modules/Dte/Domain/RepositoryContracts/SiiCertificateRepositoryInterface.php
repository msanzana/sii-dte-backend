<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCertificate;

interface SiiCertificateRepositoryInterface{
    public function create(SiiCertificate $certificate): SiiCertificate;
    public function findById(int $id) : ?SiiCertificate;
    public function findByCompany(int $companyId): array;
    public function findDefaultByCompanyId(int $companyId): ?SiiCertificate;
    public function clearDefaultByCvompanyId(int $companyId): void;
}
