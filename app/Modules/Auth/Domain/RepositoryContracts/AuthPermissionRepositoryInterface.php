<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthPermission;



interface AuthPermissionRepositoryInterface
{
    /**
     * @return AuthPermission[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array;

    public function findById(int $permissionId): ?AuthPermission;

    public function create(
        string $code,
        string $name,
        string $module,
        ?string $description,
        bool $isActive
    ): AuthPermission;

    public function update(
        int $permissionId,
        string $code,
        string $name,
        string $module,
        ?string $description
    ): AuthPermission;

    public function setActive(int $permissionId, bool $isActive): void;
}
