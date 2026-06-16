<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\CreateAuthRoleInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthRoleInputDto;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRolesRepositoryInterface;


final class ManageAuthRolesService
{
    public function __construct(
        private readonly AuthRolesRepositoryInterface $authRoleRepository,
    ) {
    }

    public function list(?bool $isActive = null, int $limit = 100): array
    {
        return array_map(
            fn ($role) => [
                'id' => $role->id(),
                'code' => $role->code(),
                'name' => $role->name(),
                'description' => $role->description(),
                'is_system' => $role->isSystem(),
                'is_active' => $role->isActive(),
                'permission_codes' => $role->permissionCodes(),
            ],
            $this->authRoleRepository->findAll($isActive, $limit)
        );
    }

    public function show(int $roleId): array
    {
        $role = $this->authRoleRepository->findById($roleId);

        if (!$role) {
            throw AuthEntityNotFoundException::for('rol', $roleId);
        }

        return [
            'id' => $role->id(),
            'code' => $role->code(),
            'name' => $role->name(),
            'description' => $role->description(),
            'is_system' => $role->isSystem(),
            'is_active' => $role->isActive(),
            'permission_codes' => $role->permissionCodes(),
        ];
    }

    public function create(CreateAuthRoleInputDto $input): array
    {
        $role = $this->authRoleRepository->create(
            code: $input->code,
            name: $input->name,
            description: $input->description,
            isSystem: $input->isSystem,
            isActive: $input->isActive
        );

        return $this->show($role->id());
    }

    public function update(UpdateAuthRoleInputDto $input): array
    {
        $existing = $this->authRoleRepository->findById($input->roleId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('rol', $input->roleId);
        }

        $role = $this->authRoleRepository->update(
            roleId: $input->roleId,
            code: $input->code,
            name: $input->name,
            description: $input->description
        );

        return $this->show($role->id());
    }

    public function setActive(int $roleId, bool $isActive): array
    {
        $existing = $this->authRoleRepository->findById($roleId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('rol', $roleId);
        }

        $this->authRoleRepository->setActive($roleId, $isActive);

        return $this->show($roleId);
    }

    /**
     * @param int[] $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): array
    {
        $existing = $this->authRoleRepository->findById($roleId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('rol', $roleId);
        }

        $this->authRoleRepository->syncPermissions($roleId, $permissionIds);

        return $this->show($roleId);
    }
}
