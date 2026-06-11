<?php
namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\UserCompanyAccess;

interface UserCompanyAccessRepositoryInterface
{
    public function findAccessibleCompaniesByUserId(int $userId): array;
    public function findAccessibleCompanyByUserAndCompany(
        int $userId,
        int $companyId
    ): ?UserCompanyAccess;
}
