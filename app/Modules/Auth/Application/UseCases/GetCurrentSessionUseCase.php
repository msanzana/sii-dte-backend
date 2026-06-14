<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\CurrentSessionInputDto;
use App\Modules\Auth\Application\DTOs\CurrentSessionResultDto;
use App\Modules\Auth\Application\Services\AccessProfileBuilderService;
use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;

final class GetCurrentSessionUseCase
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
        private readonly AccessProfileBuilderService $accessProfileBuilderService,
    ) {
    }

    public function execute(CurrentSessionInputDto $input): CurrentSessionResultDto
    {
        $payload = $this->jwtHs256Service->parseAndValidate($input->bearerToken);

        if (($payload['stage'] ?? null) !== 'access') {
            throw InvalidJwtTokenException::because(
                'El token no corresponde a una sesión de acceso.'
            );
        }

        $userId = (int) ($payload['uid'] ?? 0);
        $companyId = (int) ($payload['company_id'] ?? 0);

        $user = $this->authUserRepository->findById($userId);

        if (!$user || !$user->isActive()) {
            throw InvalidJwtTokenException::because(
                'El usuario de la sesión no es válido o está inactivo.'
            );
        }

        $company = $this->companyStateRepository->findById($companyId);

        if (!$company || !$company->isActive()) {
            throw InvalidJwtTokenException::because(
                'La empresa de la sesión no es válida o está inactiva.'
            );
        }

        $access = $this->userCompanyAccessRepository->findAccessibleCompanyByUserAndCompany(
            $userId,
            $companyId
        );

        if (!$access) {
            throw InvalidJwtTokenException::because(
                'El usuario ya no tiene acceso activo a la empresa de la sesión.'
            );
        }

        return new CurrentSessionResultDto(
            userId: $user->id(),
            fullName: $user->fullName(),
            email: $user->email(),
            companyId: $company->id(),
            companyRut: $company->rut(),
            companyName: $company->legalName(),
            accessProfile: $this->accessProfileBuilderService->build(
                $access->roleCodes(),
                $access->permissionCodes()
            ),
        );
    }
}
