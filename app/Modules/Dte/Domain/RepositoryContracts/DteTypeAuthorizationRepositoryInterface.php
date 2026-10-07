<?php

namespace App\Modules\Dte\Domain\RepositoryContracts;

interface DteTypeAuthorizationRepositoryInterface
{
    public function existsActiveTypeByCode(int $dteType): bool;

    public function isAuthorizedForCompany(
        int $companyId,
        int $dteType
    ): bool;

    public function isAuthorizedForSystem(
        int $companyId,
        int $externalSystemId,
        int $dteType
    ): bool;
}