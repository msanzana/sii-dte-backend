<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\RefreshAccessTokenInputDto;
use App\Modules\Auth\Application\Services\AccessProfileBuilderService;
use App\Modules\Auth\Application\UseCases\RefreshAccessTokenUseCase;
use App\Modules\Auth\Domain\Entities\AuthRefreshToken;
use App\Modules\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use Mockery;
use Tests\TestCase;

final class RefreshAccessTokenUseCaseTechnicalIsolationTest extends TestCase
{
    public function test_refresh_humano_rechaza_refresh_tecnico_antes_de_reconstruir_sesion(): void
    {
        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);

        $refreshService = new RefreshTokenService();
        $secret = str_repeat('a', 64);
        $publicToken = $refreshService->buildPublicToken(50, $secret);

        $refreshTokens
            ->shouldReceive('findById')
            ->once()
            ->with(50)
            ->andReturn(new AuthRefreshToken(
                id: 50,
                userId: 10,
                companyId: 1,
                tokenHash: hash('sha256', $secret),
                userAgent: null,
                ipAddress: null,
                expiresAt: now()->addHour()->format('Y-m-d H:i:s'),
                revokedAt: null,
                externalSystemId: 7,
            ));

        $users->shouldNotReceive('findById');
        $companies->shouldNotReceive('findById');
        $accesses->shouldNotReceive('findAccessibleCompanyByUserAndCompany');
        $refreshTokens->shouldNotReceive('create');
        $refreshTokens->shouldNotReceive('revoke');

        $useCase = new RefreshAccessTokenUseCase(
            authRefreshTokenRepository: $refreshTokens,
            authUserRepository: $users,
            companyStateRepository: $companies,
            userCompanyAccessRepository: $accesses,
            jwtHs256Service: new JwtHs256Service(),
            refreshTokenService: $refreshService,
            accessProfileBuilderService: new AccessProfileBuilderService(),
        );

        $this->expectException(InvalidRefreshTokenException::class);

        $useCase->execute(new RefreshAccessTokenInputDto(
            refreshToken: $publicToken,
            userAgent: 'PHPUnit',
            ipAddress: '127.0.0.1',
        ));
    }
}
