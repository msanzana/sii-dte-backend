<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\CreateAuthPermissionInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthPermissionInputDto;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPermissionRepositoryInterface;

final class ManageAuthPermissionsService
{
    public function __construct(
        private readonly AuthPermissionRepositoryInterface $authPermissionRepository,
    ) {
    }

    public function list(?bool $isActive = null, int $limit = 100): array
    {
        return array_map(
            fn ($permission) => [
                'id' => $permission->id(),
                'code' => $permission->code(),
                'name' => $permission->name(),
                'module' => $permission->module(),
                'description' => $permission->description(),
                'is_active' => $permission->isActive(),
            ],
            $this->authPermissionRepository->findAll($isActive, $limit)
        );
    }

    public function show(int $permissionId): array
    {
        $permission = $this->authPermissionRepository->findById($permissionId);

        if (!$permission) {
            throw AuthEntityNotFoundException::for('permiso', $permissionId);
        }

        return [
            'id' => $permission->id(),
            'code' => $permission->code(),
            'name' => $permission->name(),
            'module' => $permission->module(),
            'description' => $permission->description(),
            'is_active' => $permission->isActive(),
        ];
    }

    public function create(CreateAuthPermissionInputDto $input): array
    {
        $permission = $this->authPermissionRepository->create(
            code: $input->code,
            name: $input->name,
            module: $input->module,
            description: $input->description,
            isActive: $input->isActive
        );

        return $this->show($permission->id());
    }

    public function update(UpdateAuthPermissionInputDto $input): array
    {
        $existing = $this->authPermissionRepository->findById($input->permissionId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('permiso', $input->permissionId);
        }

        $permission = $this->authPermissionRepository->update(
            permissionId: $input->permissionId,
            code: $input->code,
            name: $input->name,
            module: $input->module,
            description: $input->description
        );

        return $this->show($permission->id());
    }

    public function setActive(int $permissionId, bool $isActive): array
    {
        $existing = $this->authPermissionRepository->findById($permissionId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('permiso', $permissionId);
        }

        $this->authPermissionRepository->setActive($permissionId, $isActive);

        return $this->show($permissionId);
    }
}
