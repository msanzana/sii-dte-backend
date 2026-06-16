<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\SelectCompanyInputDto;
use App\Modules\Auth\Application\DTOs\SelectCompanyResultDto;
use App\Modules\Auth\Application\Services\AccessProfileBuilderService;
use App\Modules\Auth\Domain\Exception\CompanySelectionNotAllowedException;
use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use Illuminate\Support\Facades\DB;

final class SelectCompanyUseCase
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AccessProfileBuilderService $accessProfileBuilderService,
    ) {
    }

    public function execute(SelectCompanyInputDto $input): SelectCompanyResultDto
    {
        return DB::transaction(function () use ($input) {
            $payload = $this->jwtHs256Service->parseAndValidate($input->bearerToken);

            if (($payload['stage'] ?? null) !== 'pre_company') {
                throw InvalidJwtTokenException::because(
                    'El token actual no corresponde a la etapa de selección de empresa.'
                );
            }

            $userId = (int) $payload['uid'];

            $user = $this->authUserRepository->findById($userId);

            if (!$user || !$user->isActive()) {
                throw InvalidJwtTokenException::because(
                    'El usuario asociado al token no es válido o está inactivo.'
                );
            }

            $access = $this->userCompanyAccessRepository->findAccessibleCompanyByUserAndCompany(
                $userId,
                $input->companyId
            );

            if (!$access || !$access->canSelectCompany()) {
                throw CompanySelectionNotAllowedException::forUserAndCompany(
                    $userId,
                    $input->companyId
                );
            }

            $accessProfile = $this->accessProfileBuilderService->build(
                $access->roleCodes(),
                $access->permissionCodes(),
                $access->permissionItems()
            );

            $accessToken = $this->jwtHs256Service->issue([
                'sub' => (string) $user->id(),
                'uid' => $user->id(),
                'email' => $user->email(),
                'stage' => 'access',
                'company_id' => $access->companyId(),
                'roles' => $accessProfile->roles,
                'permissions' => $accessProfile->permissions,
            ], (int) config('platform_auth.jwt.ttl_seconds.access', 28800));

            $refreshExpiresInSeconds = (int) config('platform_auth.refresh_tokens.ttl_seconds', 2592000);

            $refreshSecret = $this->refreshTokenService->generateSecret();

            $refreshRecord = $this->authRefreshTokenRepository->create(
                userId: $user->id(),
                companyId: $access->companyId(),
                tokenHash: $this->refreshTokenService->hashSecret($refreshSecret),
                expiresAt: now()->addSeconds($refreshExpiresInSeconds)->format('Y-m-d H:i:s'),
                userAgent: $input->userAgent,
                ipAddress: $input->ipAddress,
            );

            return new SelectCompanyResultDto(
                userId: $user->id(),
                fullName: $user->fullName(),
                email: $user->email(),
                companyId: $access->companyId(),
                companyRut: $access->companyRut(),
                companyName: $access->companyName(),
                accessToken: $accessToken,
                accessExpiresInSeconds: (int) config('platform_auth.jwt.ttl_seconds.access', 28800),
                refreshToken: $this->refreshTokenService->buildPublicToken(
                    $refreshRecord->id(),
                    $refreshSecret
                ),
                refreshExpiresInSeconds: $refreshExpiresInSeconds,
                accessProfile: $accessProfile,
            );
        });
    }
}
