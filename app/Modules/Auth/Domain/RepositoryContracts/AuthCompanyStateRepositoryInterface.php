<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\ManagedCompany;

interface AuthCompanyStateRepositoryInterface
{
    /**
     * @return ManagedCompany[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array;

    public function findById(int $companyId): ?ManagedCompany;

    public function setActive(int $companyId, bool $isActive): void;
}
