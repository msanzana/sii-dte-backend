<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\TechnicalRefreshAccessTokenInputDto;
use App\Modules\Auth\Application\DTOs\TechnicalRefreshAccessTokenResultDto;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class TechnicalRefreshAccessTokenUseCase
{
    public function __construct(
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly TechnicalUserSystemAccessRepositoryInterface $technicalUserSystemAccessRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly ExternalSystemRepositoryInterface $externalSystemRepository,
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function execute(
        TechnicalRefreshAccessTokenInputDto $input
    ): TechnicalRefreshAccessTokenResultDto {
        return DB::transaction(function () use ($input): TechnicalRefreshAccessTokenResultDto {
            $parsed = $this->refreshTokenService->parsePublicToken(
                $input->refreshToken
            );

            $refreshToken = $this->authRefreshTokenRepository->findById(
                $parsed['id']
            );

            if ($refreshToken === null) {
                throw InvalidRefreshTokenException::because(
                    'El refresh token no existe.'
                );
            }

            if (!hash_equals($refreshToken->tokenHash(), $parsed['hash'])) {
                throw InvalidRefreshTokenException::because(
                    'El refresh token no es válido.'
                );
            }

            if ($refreshToken->isRevoked()) {
                throw InvalidRefreshTokenException::because(
                    'El refresh token ya fue revocado.'
                );
            }

            if (now()->gte($refreshToken->expiresAt())) {
                throw InvalidRefreshTokenException::because(
                    'El refresh token expiró.'
                );
            }

            $externalSystemId = $refreshToken->externalSystemId();

            if ($externalSystemId === null) {
                throw InvalidRefreshTokenException::because(
                    'El refresh token no corresponde a una sesión técnica.'
                );
            }

            $user = $this->authUserRepository->findById(
                $refreshToken->userId()
            );

            if (
                $user === null
                || !$user->isActive()
                || !$user->isTechnical()
            ) {
                throw InvalidRefreshTokenException::because(
                    'El usuario del refresh token no es válido o está inactivo.'
                );
            }

            $activeAccesses = $this->technicalUserSystemAccessRepository
                ->findActiveByUserId($user->id());

            if (count($activeAccesses) !== 1) {
                throw InvalidRefreshTokenException::because(
                    'El ámbito técnico del refresh token ya no es válido.'
                );
            }

            $technicalAccess = $activeAccesses[0];

            if (
                $technicalAccess->companyId() !== $refreshToken->companyId()
                || $technicalAccess->externalSystemId() !== $externalSystemId
            ) {
                throw InvalidRefreshTokenException::because(
                    'El ámbito técnico del refresh token ya no es válido.'
                );
            }

            $company = $this->companyStateRepository->findById(
                $refreshToken->companyId()
            );

            if ($company === null || !$company->isActive()) {
                throw InvalidRefreshTokenException::because(
                    'La empresa del refresh token no es válida o está inactiva.'
                );
            }

            $externalSystem = $this->externalSystemRepository->findByCompanyAndId(
                $company->id(),
                $externalSystemId
            );

            if (
                $externalSystem === null
                || !$externalSystem->isActive()
                || $externalSystem->companyId() !== $company->id()
            ) {
                throw InvalidRefreshTokenException::because(
                    'El sistema externo del refresh token no es válido o está inactivo.'
                );
            }

            $accessExpiresInSeconds = (int) config(
                'platform_auth.jwt.ttl_seconds.access',
                28800
            );

            $accessToken = $this->jwtHs256Service->issue([
                'sub' => (string) $user->id(),
                'uid' => $user->id(),
                'account_type' => AuthUser::ACCOUNT_TYPE_TECHNICAL,
                'stage' => 'access',
                'company_id' => $company->id(),
                'external_system_id' => $externalSystemId,
                'roles' => [],
                'permissions' => [],
            ], $accessExpiresInSeconds);

            $rotate = (bool) config(
                'platform_auth.refresh_tokens.rotate_on_refresh',
                true
            );

            $refreshExpiresInSeconds = (int) config(
                'platform_auth.refresh_tokens.ttl_seconds',
                2592000
            );

            $publicRefreshToken = $input->refreshToken;

            if ($rotate) {
                $newSecret = $this->refreshTokenService->generateSecret();

                $newRecord = $this->authRefreshTokenRepository->create(
                    userId: $user->id(),
                    companyId: $company->id(),
                    tokenHash: $this->refreshTokenService->hashSecret($newSecret),
                    expiresAt: now()->addSeconds($refreshExpiresInSeconds)->format('Y-m-d H:i:s'),
                    userAgent: $input->userAgent,
                    ipAddress: $input->ipAddress,
                    externalSystemId: $externalSystemId,
                );

                $this->authRefreshTokenRepository->revoke(
                    $refreshToken->id()
                );

                $publicRefreshToken = $this->refreshTokenService->buildPublicToken(
                    $newRecord->id(),
                    $newSecret
                );
            }

            return new TechnicalRefreshAccessTokenResultDto(
                userId: $user->id(),
                companyId: $company->id(),
                companyRut: $company->rut(),
                companyName: $company->legalName(),
                externalSystemId: $externalSystemId,
                externalSystemCode: $externalSystem->code(),
                externalSystemName: $externalSystem->name(),
                accessToken: $accessToken,
                accessExpiresInSeconds: $accessExpiresInSeconds,
                refreshToken: $publicRefreshToken,
                refreshExpiresInSeconds: $refreshExpiresInSeconds,
            );
        });
    }
}
