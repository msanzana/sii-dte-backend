<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\AssignUserCompanyInputDto;
use App\Modules\Auth\Application\DTOs\UpdateUserCompanyAssignmentInputDto;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRoleRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;

final class ManageUserCompanyAssignmentsService
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthCompanyStateRepositoryInterface $authCompanyStateRepository,
        private readonly AuthRoleRepositoryInterface $authRoleRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
    ) {
    }

    public function listByUser(int $userId): array
    {
        $user = $this->authUserRepository->findById($userId);

        if (!$user) {
            throw AuthEntityNotFoundException::for('usuario', $userId);
        }

        return array_map(
            fn ($assignment) => [
                'access_id' => $assignment->accessId(),
                'user_id' => $assignment->userId(),
                'company_id' => $assignment->companyId(),
                'company_rut' => $assignment->companyRut(),
                'company_name' => $assignment->companyName(),
                'is_active' => $assignment->isActive(),
                'is_default' => $assignment->isDefault(),
                'can_select_company' => $assignment->canSelectCompany(),
                'roles' => $assignment->roleCodes(),
                'permissions' => $assignment->permissionCodes(),
            ],
            $this->userCompanyAccessRepository->findAssignmentsByUserId($userId)
        );
    }

    public function assign(AssignUserCompanyInputDto $input): array
    {
        if (!$this->authUserRepository->findById($input->userId)) {
            throw AuthEntityNotFoundException::for('usuario', $input->userId);
        }

        $company = $this->authCompanyStateRepository->findById($input->companyId);

        if (!$company) {
            throw AuthEntityNotFoundException::for('empresa', $input->companyId);
        }

        foreach ($input->roleIds as $roleId) {
            if (!$this->authRoleRepository->findById((int) $roleId)) {
                throw AuthEntityNotFoundException::for('rol', (int) $roleId);
            }
        }

        $assignment = $this->userCompanyAccessRepository->assignCompanyToUser(
            userId: $input->userId,
            companyId: $input->companyId,
            isDefault: $input->isDefault,
            canSelectCompany: $input->canSelectCompany,
            roleIds: $input->roleIds
        );

        return [
            'access_id' => $assignment->accessId(),
            'user_id' => $assignment->userId(),
            'company_id' => $assignment->companyId(),
            'company_rut' => $assignment->companyRut(),
            'company_name' => $assignment->companyName(),
            'is_active' => $assignment->isActive(),
            'is_default' => $assignment->isDefault(),
            'can_select_company' => $assignment->canSelectCompany(),
            'roles' => $assignment->roleCodes(),
            'permissions' => $assignment->permissionCodes(),
        ];
    }

    public function update(UpdateUserCompanyAssignmentInputDto $input): array
    {
        $current = $this->userCompanyAccessRepository->findByAccessId($input->accessId);

        if (!$current) {
            throw AuthEntityNotFoundException::for('acceso usuario-empresa', $input->accessId);
        }

        foreach ($input->roleIds as $roleId) {
            if (!$this->authRoleRepository->findById((int) $roleId)) {
                throw AuthEntityNotFoundException::for('rol', (int) $roleId);
            }
        }

        $assignment = $this->userCompanyAccessRepository->updateAssignment(
            accessId: $input->accessId,
            isDefault: $input->isDefault,
            canSelectCompany: $input->canSelectCompany,
            roleIds: $input->roleIds
        );

        return [
            'access_id' => $assignment->accessId(),
            'user_id' => $assignment->userId(),
            'company_id' => $assignment->companyId(),
            'company_rut' => $assignment->companyRut(),
            'company_name' => $assignment->companyName(),
            'is_active' => $assignment->isActive(),
            'is_default' => $assignment->isDefault(),
            'can_select_company' => $assignment->canSelectCompany(),
            'roles' => $assignment->roleCodes(),
            'permissions' => $assignment->permissionCodes(),
        ];
    }

    public function setActive(int $accessId, bool $isActive): array
    {
        $current = $this->userCompanyAccessRepository->findByAccessId($accessId);

        if (!$current) {
            throw AuthEntityNotFoundException::for('acceso usuario-empresa', $accessId);
        }

        $this->userCompanyAccessRepository->setAssignmentActive($accessId, $isActive);

        $updated = $this->userCompanyAccessRepository->findByAccessId($accessId);

        return [
            'access_id' => $updated->accessId(),
            'user_id' => $updated->userId(),
            'company_id' => $updated->companyId(),
            'company_rut' => $updated->companyRut(),
            'company_name' => $updated->companyName(),
            'is_active' => $updated->isActive(),
            'is_default' => $updated->isDefault(),
            'can_select_company' => $updated->canSelectCompany(),
            'roles' => $updated->roleCodes(),
            'permissions' => $updated->permissionCodes(),
        ];
    }

    public function remove(int $accessId): void
    {
        $current = $this->userCompanyAccessRepository->findByAccessId($accessId);

        if (!$current) {
            throw AuthEntityNotFoundException::for('acceso usuario-empresa', $accessId);
        }

        $this->userCompanyAccessRepository->removeAssignment($accessId);
    }
}
