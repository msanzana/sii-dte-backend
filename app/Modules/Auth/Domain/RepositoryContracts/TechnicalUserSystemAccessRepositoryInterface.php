<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;

interface TechnicalUserSystemAccessRepositoryInterface
{
    public function grant(
        int $userId,
        int $companyId,
        int $externalSystemId
    ): void;

    /**
     * @return TechnicalUserSystemAccess[]
     */
    public function findActiveByUserId(int $userId): array;
}
