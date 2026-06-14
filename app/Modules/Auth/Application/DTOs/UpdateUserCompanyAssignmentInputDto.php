<?php

namespace App\Modules\Auth\Application\DTOs;

final class UpdateUserCompanyAssignmentInputDto
{
    /**
     * @param int[] $roleIds
     */
    public function __construct(
        public readonly int $accessId,
        public readonly bool $isDefault,
        public readonly bool $canSelectCompany,
        public readonly array $roleIds,
    ) {
    }
}
