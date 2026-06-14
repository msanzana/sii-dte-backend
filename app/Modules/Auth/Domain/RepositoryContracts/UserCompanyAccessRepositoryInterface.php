<?php
namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\UserCompanyAccess;

interface UserCompanyAccessRepositoryInterface
{
 /**
     * @return UserCompanyAccess[]
     */
    public function findAccessibleCompaniesByUserId(int $userId): array;

    public function findAccessibleCompanyByUserAndCompany(
        int $userId,
        int $companyId
    ): ?UserCompanyAccess;

    /**
     * @return UserCompanyAccess[]
     */
    public function findAssignmentsByUserId(int $userId): array;

    public function findByAccessId(int $accessId): ?UserCompanyAccess;

    /**
     * @param int[] $roleIds
     */
    public function assignCompanyToUser(
        int $userId,
        int $companyId,
        bool $isDefault,
        bool $canSelectCompany,
        array $roleIds
    ): UserCompanyAccess;

    /**
     * @param int[] $roleIds
     */
    public function updateAssignment(
        int $accessId,
        bool $isDefault,
        bool $canSelectCompany,
        array $roleIds
    ): UserCompanyAccess;

    public function setAssignmentActive(int $accessId, bool $isActive): void;

    public function removeAssignment(int $accessId): void;

    public function hasActiveAccess(int $userId, int $companyId): bool;
}
