<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\ListAccessibleCompaniesInputDto;
use App\Modules\Auth\Application\DTOs\ListAccessibleCompaniesResultDto;
use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;

final class ListAccessibleCompaniesUseCase
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
    ) {
    }

    public function execute(
        ListAccessibleCompaniesInputDto $input
    ): ListAccessibleCompaniesResultDto {

        $payload = $this->jwtHs256Service->parseAndValidate($input->bearerToken);
        if (($payload['stage'] ?? null) !== 'pre_company') {
            throw InvalidJwtTokenException::because(
                'El token no corresponde a la etapa pre_company.'
            );
        }

        $userId = (int) $payload['uid'];

        $companies = $this->userCompanyAccessRepository->findAccessibleCompaniesByUserId($userId);

        return new ListAccessibleCompaniesResultDto(
            userId: $userId,
            companies: array_map(
                fn ($access) => [
                    'company_id' => $access->companyId(),
                    'company_rut' => $access->companyRut(),
                    'company_name' => $access->companyName(),
                    'is_default' => $access->isDefault(),
                    'roles' => $access->roleCodes(),
                    'permissions' => $access->permissionCodes(),
                    'permission_items' => $access->permissionItems(),
                ],
                $companies
            )
        );
    }
}
