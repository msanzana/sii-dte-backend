<?php
namespace App\Modules\Auth\Domain\Entities;

final class UserCompanyAccess
{
    public function __construct(
        private readonly int $accessId,
        private readonly int $userId,
        private readonly int $companyId,
        private readonly string $companyRut,
        private readonly string $companyName,
        private readonly bool $isActive,
        private readonly bool $isDefault,
        private readonly bool $canSelectCompany,
        private readonly array $roleCodes = [],
        private readonly array $permissionCodes = [],
        private readonly array $permissionItems = [],
    )
    {}

    public function accessId():int
    {
        return $this->accessId;
    }

    public function userId():int
    {
        return $this->userId;
    }

    public function companyId():int
    {
        return $this->companyId;
    }
    public function CompanyRut():string
    {
        return $this->companyRut;
    }

    public function companyName():string
    {
        return $this->companyName;
    }

    public function isActive():bool
    {
        return $this->isActive;
    }

    public function isDefault():bool
    {
        return $this->isDefault;
    }

    public function canSelectCompany():bool
    {
        return $this->canSelectCompany;
    }

    public function roleCodes():array
    {
        return $this->roleCodes;
    }

    public function permissionCodes():array
    {
        return $this->permissionCodes;
    }
    public function permissionItems():array
    {
        return $this->permissionItems;
    }
}
