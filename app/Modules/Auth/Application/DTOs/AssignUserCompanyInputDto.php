<?php

namespace App\Modules\Auth\Application\DTOs;

final class AssignUserCompanyInputDto
{
    /**
     * @param int[] $roleIds
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $companyId,
        public readonly bool $isDefault,
        public readonly bool $canSelectCompany,
        public readonly array $roleIds,
    ) {
    }
}
