<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\RefreshAccessTokenInputDto;
use App\Modules\Auth\Application\DTOs\RefreshAccessTokenResultDto;
use App\Modules\Auth\Application\Services\AccessProfileBuilderService;
use App\Modules\Auth\Domain\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use Illuminate\Support\Facades\DB;

final class RefreshAccessTokenUseCase
{
    public function __construct(
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AccessProfileBuilderService $accessProfileBuilderService,
        ) {
    }

    public function execute(RefreshAccessTokenInputDto $input): RefreshAccessTokenResultDto
    {
        return DB::transaction(function () use ($input) {
            $parsed = $this->refreshTokenService->parsePublicToken($input->refreshToken);

            $refreshToken = $this->authRefreshTokenRepository->findById($parsed['id']);

            if (!$refreshToken) {
                throw InvalidRefreshTokenException::because('El refresh token no existe.');
            }

            if (!hash_equals($refreshToken->tokenHash(), $parsed['hash'])) {
                throw InvalidRefreshTokenException::because('El refresh token no es válido.');
            }

            if ($refreshToken->isRevoked()) {
                throw InvalidRefreshTokenException::because('El refresh token ya fue revocado.');
            }

            if (now()->gte($refreshToken->expiresAt())) {
                throw InvalidRefreshTokenException::because('El refresh token expiró.');
            }

            $user = $this->authUserRepository->findById($refreshToken->userId());

            if (!$user || !$user->isActive()) {
                throw InvalidRefreshTokenException::because(
                    'El usuario del refresh token no es válido o está inactivo.'
                );
            }

            $company = $this->companyStateRepository->findById($refreshToken->companyId());

            if (!$company || !$company->isActive()) {
                throw InvalidRefreshTokenException::because(
                    'La empresa del refresh token no es válida o está inactiva.'
                );
            }

            $access = $this->userCompanyAccessRepository->findAccessibleCompanyByUserAndCompany(
                $user->id(),
                $company->id()
            );

            if (!$access) {
                throw InvalidRefreshTokenException::because(
                    'El usuario ya no tiene acceso activo a la empresa del refresh token.'
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
                'company_id' => $company->id(),
                'roles' => $access->roleCodes(),
                'permissions' => $access->permissionCodes(),
            ], (int) config('platform_auth.jwt.ttl_seconds.access', 28800));

            $rotate = (bool) config('platform_auth.refresh_tokens.rotate_on_refresh', true);
            $refreshExpiresInSeconds = (int) config('platform_auth.refresh_tokens.ttl_seconds', 2592000);

            $publicRefreshToken = $input->refreshToken;

            if ($rotate) {
                $newSecret = $this->refreshTokenService->generateSecret();

                $newRecord = $this->authRefreshTokenRepository->create(
                    userId: $user->id(),
                    companyId: $company->id(),
                    tokenHash: $this->refreshTokenService->hashSecret($newSecret),
                    expiresAt: now()->addSeconds($refreshExpiresInSeconds)->format('Y-m-d H:i:s'),
                    userAgent: $input->userAgent,
                    ipAddress: $input->ipAddress
                );

                $this->authRefreshTokenRepository->revoke($refreshToken->id());

                $publicRefreshToken = $this->refreshTokenService->buildPublicToken(
                    $newRecord->id(),
                    $newSecret
                );
            }

            return new RefreshAccessTokenResultDto(
                userId: $user->id(),
                companyId: $company->id(),
                companyRut: $company->rut(),
                companyName: $company->legalName(),
                accessToken: $accessToken,
                accessExpiresInSeconds: (int) config('platform_auth.jwt.ttl_seconds.access', 28800),
                refreshToken: $publicRefreshToken,
                refreshExpiresInSeconds: $refreshExpiresInSeconds,
                accessProfile: $accessProfile,
            );
        });
    }
}
