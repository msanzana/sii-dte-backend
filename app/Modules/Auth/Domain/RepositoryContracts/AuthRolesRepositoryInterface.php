<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthRole;



interface AuthRoleRepositoryInterface
{
    /**
     * @return AuthRole[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array;

    public function findById(int $roleId): ?AuthRole;

    public function create(
        string $code,
        string $name,
        ?string $description,
        bool $isSystem,
        bool $isActive
    ): AuthRole;

    public function update(
        int $roleId,
        string $code,
        string $name,
        ?string $description
    ): AuthRole;

    public function setActive(int $roleId, bool $isActive): void;

    /**
     * @param int[] $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): AuthRole;
}
