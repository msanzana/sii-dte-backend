<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;

final class ManageCompaniesStateService
{
    public function __construct(
        private readonly AuthCompanyStateRepositoryInterface $authCompanyStateRepository,
    ) {
    }

    public function list(?bool $isActive = null, int $limit = 100): array
    {
        return array_map(
            fn ($company) => [
                'id' => $company->id(),
                'rut' => $company->rut(),
                'legal_name' => $company->legalName(),
                'is_active' => $company->isActive(),
            ],
            $this->authCompanyStateRepository->findAll($isActive, $limit)
        );
    }

    public function show(int $companyId): array
    {
        $company = $this->authCompanyStateRepository->findById($companyId);

        if (!$company) {
            throw AuthEntityNotFoundException::for('empresa', $companyId);
        }

        return [
            'id' => $company->id(),
            'rut' => $company->rut(),
            'legal_name' => $company->legalName(),
            'is_active' => $company->isActive(),
        ];
    }

    public function setActive(int $companyId, bool $isActive): array
    {
        $company = $this->authCompanyStateRepository->findById($companyId);

        if (!$company) {
            throw AuthEntityNotFoundException::for('empresa', $companyId);
        }

        $this->authCompanyStateRepository->setActive($companyId, $isActive);

        return $this->show($companyId);
    }
}
