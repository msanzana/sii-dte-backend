<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\TechnicalLoginInputDto;
use App\Modules\Auth\Application\DTOs\TechnicalLoginResultDto;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Exception\InvalidCredentialsException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class TechnicalLoginUseCase
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly TechnicalUserSystemAccessRepositoryInterface $technicalUserSystemAccessRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly ExternalSystemRepositoryInterface $externalSystemRepository,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function execute(
        TechnicalLoginInputDto $input
    ): TechnicalLoginResultDto {
        return DB::transaction(function () use ($input): TechnicalLoginResultDto {
            $user = $this->authUserRepository->findActiveTechnicalByLoginUuid(
                $input->loginUuid
            );

            if ($user === null || !$user->isTechnical()) {
                throw InvalidCredentialsException::because();
            }

            if (!Hash::check($input->password, $user->passwordHash())) {
                throw InvalidCredentialsException::because();
            }

            $activeAccesses = $this->technicalUserSystemAccessRepository
                ->findActiveByUserId($user->id());

            // Una credencial tecnica representa exactamente un ambito empresa+sistema.
            if (count($activeAccesses) !== 1) {
                throw InvalidCredentialsException::because();
            }

            $technicalAccess = $activeAccesses[0];

            $company = $this->companyStateRepository->findById(
                $technicalAccess->companyId()
            );

            if ($company === null || !$company->isActive()) {
                throw InvalidCredentialsException::because();
            }

            $externalSystem = $this->externalSystemRepository->findByCompanyAndId(
                $company->id(),
                $technicalAccess->externalSystemId()
            );

            if (
                $externalSystem === null
                || !$externalSystem->isActive()
                || $externalSystem->companyId() !== $company->id()
            ) {
                throw InvalidCredentialsException::because();
            }

            $externalSystemId = (int) $externalSystem->id();
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

            $refreshExpiresInSeconds = (int) config(
                'platform_auth.refresh_tokens.ttl_seconds',
                2592000
            );

            $refreshSecret = $this->refreshTokenService->generateSecret();

            $refreshRecord = $this->authRefreshTokenRepository->create(
                userId: $user->id(),
                companyId: $company->id(),
                tokenHash: $this->refreshTokenService->hashSecret($refreshSecret),
                expiresAt: now()->addSeconds($refreshExpiresInSeconds)->format('Y-m-d H:i:s'),
                userAgent: $input->userAgent,
                ipAddress: $input->ipAddress,
                externalSystemId: $externalSystemId,
            );

            $this->authUserRepository->touchLastLoginAt($user->id());

            return new TechnicalLoginResultDto(
                userId: $user->id(),
                loginUuid: (string) $user->loginUuid(),
                companyId: $company->id(),
                companyRut: $company->rut(),
                companyName: $company->legalName(),
                externalSystemId: $externalSystemId,
                externalSystemCode: $externalSystem->code(),
                externalSystemName: $externalSystem->name(),
                accessToken: $accessToken,
                accessExpiresInSeconds: $accessExpiresInSeconds,
                refreshToken: $this->refreshTokenService->buildPublicToken(
                    $refreshRecord->id(),
                    $refreshSecret
                ),
                refreshExpiresInSeconds: $refreshExpiresInSeconds,
            );
        });
    }
}
