<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
interface ExternalSystemRepositoryInterface
{
    public function create(ExternalSystem $externalSystem): ExternalSystem;
    public function update(ExternalSystem $externalSystem): ExternalSystem;
    public function findById(int $id): ?ExternalSystem;
    public function findByCompanyAndId(int $companyId, int $id): ?ExternalSystem;
    public function findByCompanyAndCode(int $companyId, string $code): ?ExternalSystem;
    public function findByCompanyId(int $companyId): array;
    public function setActive(int $id, bool $isActive): void;
}
