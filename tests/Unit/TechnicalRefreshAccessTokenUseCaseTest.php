<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\TechnicalRefreshAccessTokenInputDto;
use App\Modules\Auth\Application\UseCases\TechnicalRefreshAccessTokenUseCase;
use App\Modules\Auth\Domain\Entities\AuthRefreshToken;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Entities\ManagedCompany;
use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;
use App\Modules\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Auth\Infrastructure\Security\RefreshTokenService;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

final class TechnicalRefreshAccessTokenUseCaseTest extends TestCase
{
    private const SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private AuthRefreshTokenRepositoryInterface $refreshTokens;
    private AuthUserRepositoryInterface $users;
    private TechnicalUserSystemAccessRepositoryInterface $technicalAccesses;
    private AuthCompanyStateRepositoryInterface $companies;
    private ExternalSystemRepositoryInterface $systems;
    private JwtHs256Service $jwt;
    private RefreshTokenService $refreshTokenService;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('platform_auth.jwt.secret', 'technical-refresh-test-secret');
        config()->set('platform_auth.jwt.issuer', 'sii-dte-backend-test');
        config()->set('platform_auth.jwt.audience', 'sii-dte-platform-test');
        config()->set('platform_auth.jwt.ttl_seconds.access', 3600);
        config()->set('platform_auth.refresh_tokens.ttl_seconds', 7200);
        config()->set('platform_auth.refresh_tokens.rotate_on_refresh', true);

        $this->refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $this->users = Mockery::mock(AuthUserRepositoryInterface::class);
        $this->technicalAccesses = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $this->companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $this->systems = Mockery::mock(ExternalSystemRepositoryInterface::class);
        $this->jwt = new JwtHs256Service();
        $this->refreshTokenService = new RefreshTokenService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_refresh_tecnico_rota_token_y_conserva_ambito_servidor(): void
    {
        $this->expectBaseRefreshLookup($this->technicalRefreshToken());
        $this->expectValidTechnicalScope();

        $this->refreshTokens
            ->shouldReceive('create')
            ->once()
            ->withArgs(fn (
                int $userId,
                int $companyId,
                string $tokenHash,
                string $expiresAt,
                ?string $userAgent,
                ?string $ipAddress,
                ?int $externalSystemId,
            ): bool => $userId === 10
                && $companyId === 1
                && strlen($tokenHash) === 64
                && $expiresAt !== ''
                && $userAgent === 'PHPUnit'
                && $ipAddress === '127.0.0.1'
                && $externalSystemId === 7)
            ->andReturnUsing(fn (
                int $userId,
                int $companyId,
                string $tokenHash,
                string $expiresAt,
                ?string $userAgent,
                ?string $ipAddress,
                ?int $externalSystemId,
            ): AuthRefreshToken => new AuthRefreshToken(
                id: 51,
                userId: $userId,
                companyId: $companyId,
                tokenHash: $tokenHash,
                userAgent: $userAgent,
                ipAddress: $ipAddress,
                expiresAt: $expiresAt,
                revokedAt: null,
                externalSystemId: $externalSystemId,
            ));

        $this->refreshTokens
            ->shouldReceive('revoke')
            ->once()
            ->with(50);

        $result = $this->useCase()->execute($this->input());

        $this->assertSame(10, $result->userId);
        $this->assertSame(1, $result->companyId);
        $this->assertSame(7, $result->externalSystemId);
        $this->assertSame(3600, $result->accessExpiresInSeconds);
        $this->assertSame(7200, $result->refreshExpiresInSeconds);
        $this->assertNotSame($this->publicRefreshToken(), $result->refreshToken);

        $payload = $this->jwt->parseAndValidate($result->accessToken);

        $this->assertSame(AuthUser::ACCOUNT_TYPE_TECHNICAL, $payload['account_type']);
        $this->assertSame(1, $payload['company_id']);
        $this->assertSame(7, $payload['external_system_id']);
        $this->assertSame([], $payload['roles']);
        $this->assertSame([], $payload['permissions']);

        $parsedRefresh = $this->refreshTokenService->parsePublicToken($result->refreshToken);
        $this->assertSame(51, $parsedRefresh['id']);
    }

    public function test_refresh_tecnico_rechaza_refresh_humano(): void
    {
        $token = $this->technicalRefreshToken(externalSystemId: null);
        $this->expectBaseRefreshLookup($token);

        $this->users->shouldNotReceive('findById');
        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->refreshTokens->shouldNotReceive('create');
        $this->refreshTokens->shouldNotReceive('revoke');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    public function test_refresh_tecnico_rechaza_usuario_humano(): void
    {
        $this->expectBaseRefreshLookup($this->technicalRefreshToken());

        $this->users
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($this->humanUser());

        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->refreshTokens->shouldNotReceive('create');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    public function test_refresh_tecnico_rechaza_ambito_activo_distinto_al_persistido(): void
    {
        $this->expectBaseRefreshLookup($this->technicalRefreshToken());

        $this->users
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($this->technicalUser());

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([$this->technicalAccess(systemId: 8)]);

        $this->companies->shouldNotReceive('findById');
        $this->refreshTokens->shouldNotReceive('create');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    public function test_refresh_tecnico_rechaza_empresa_inactiva(): void
    {
        $this->expectBaseRefreshLookup($this->technicalRefreshToken());

        $this->users
            ->shouldReceive('findById')
            ->once()
            ->andReturn($this->technicalUser());

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->andReturn([$this->technicalAccess()]);

        $this->companies
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(new ManagedCompany(
                id: 1,
                rut: '76000000-0',
                legalName: 'Empresa Test',
                isActive: false,
            ));

        $this->systems->shouldNotReceive('findByCompanyAndId');
        $this->refreshTokens->shouldNotReceive('create');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    public function test_refresh_tecnico_rechaza_sistema_inactivo(): void
    {
        $this->expectBaseRefreshLookup($this->technicalRefreshToken());

        $this->users
            ->shouldReceive('findById')
            ->once()
            ->andReturn($this->technicalUser());

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->andReturn([$this->technicalAccess()]);

        $this->companies
            ->shouldReceive('findById')
            ->once()
            ->andReturn($this->activeCompany());

        $this->systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 7)
            ->andReturn(new ExternalSystem(
                id: 7,
                companyId: 1,
                code: 'ERP-1',
                name: 'ERP Test',
                description: null,
                isActive: false,
            ));

        $this->refreshTokens->shouldNotReceive('create');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    public function test_refresh_tecnico_sin_rotacion_conserva_mismo_refresh(): void
    {
        config()->set('platform_auth.refresh_tokens.rotate_on_refresh', false);

        $this->expectBaseRefreshLookup($this->technicalRefreshToken());
        $this->expectValidTechnicalScope();

        $this->refreshTokens->shouldNotReceive('create');
        $this->refreshTokens->shouldNotReceive('revoke');

        $result = $this->useCase()->execute($this->input());

        $this->assertSame($this->publicRefreshToken(), $result->refreshToken);
        $this->assertSame(7, $result->externalSystemId);
    }

    public function test_refresh_tecnico_rechaza_secret_que_no_coincide(): void
    {
        $token = $this->technicalRefreshToken(
            tokenHash: hash('sha256', str_repeat('b', 64))
        );

        $this->refreshTokens
            ->shouldReceive('findById')
            ->once()
            ->with(50)
            ->andReturn($token);

        $this->users->shouldNotReceive('findById');
        $this->refreshTokens->shouldNotReceive('create');

        $this->expectException(InvalidRefreshTokenException::class);

        $this->useCase()->execute($this->input());
    }

    private function expectBaseRefreshLookup(AuthRefreshToken $token): void
    {
        $this->refreshTokens
            ->shouldReceive('findById')
            ->once()
            ->with(50)
            ->andReturn($token);
    }

    private function expectValidTechnicalScope(): void
    {
        $this->users
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($this->technicalUser());

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([$this->technicalAccess()]);

        $this->companies
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->activeCompany());

        $this->systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 7)
            ->andReturn($this->activeSystem());
    }

    private function useCase(): TechnicalRefreshAccessTokenUseCase
    {
        return new TechnicalRefreshAccessTokenUseCase(
            authRefreshTokenRepository: $this->refreshTokens,
            authUserRepository: $this->users,
            technicalUserSystemAccessRepository: $this->technicalAccesses,
            companyStateRepository: $this->companies,
            externalSystemRepository: $this->systems,
            jwtHs256Service: $this->jwt,
            refreshTokenService: $this->refreshTokenService,
        );
    }

    private function input(): TechnicalRefreshAccessTokenInputDto
    {
        return new TechnicalRefreshAccessTokenInputDto(
            refreshToken: $this->publicRefreshToken(),
            userAgent: 'PHPUnit',
            ipAddress: '127.0.0.1',
        );
    }

    private function publicRefreshToken(): string
    {
        return $this->refreshTokenService->buildPublicToken(50, self::SECRET);
    }

    private function technicalRefreshToken(
        ?int $externalSystemId = 7,
        ?string $tokenHash = null,
    ): AuthRefreshToken {
        return new AuthRefreshToken(
            id: 50,
            userId: 10,
            companyId: 1,
            tokenHash: $tokenHash ?? hash('sha256', self::SECRET),
            userAgent: 'old-agent',
            ipAddress: '10.0.0.1',
            expiresAt: now()->addHour()->format('Y-m-d H:i:s'),
            revokedAt: null,
            externalSystemId: $externalSystemId,
        );
    }

    private function technicalUser(): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'technical-user',
            email: 'technical-user@technical.invalid',
            accountType: AuthUser::ACCOUNT_TYPE_TECHNICAL,
            loginUuid: '11111111-2222-4333-8444-555555555555',
            passwordHash: Hash::make('unused'),
            isActive: true,
            isVisible: false,
            lastLoginAt: null,
        );
    }

    private function humanUser(): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'Human User',
            email: 'human@example.test',
            accountType: AuthUser::ACCOUNT_TYPE_HUMAN,
            loginUuid: null,
            passwordHash: Hash::make('unused'),
            isActive: true,
            isVisible: true,
            lastLoginAt: null,
        );
    }

    private function technicalAccess(int $systemId = 7): TechnicalUserSystemAccess
    {
        return new TechnicalUserSystemAccess(
            id: 20,
            userId: 10,
            companyId: 1,
            externalSystemId: $systemId,
            isActive: true,
        );
    }

    private function activeCompany(): ManagedCompany
    {
        return new ManagedCompany(
            id: 1,
            rut: '76000000-0',
            legalName: 'Empresa Test',
            isActive: true,
        );
    }

    private function activeSystem(): ExternalSystem
    {
        return new ExternalSystem(
            id: 7,
            companyId: 1,
            code: 'ERP-1',
            name: 'ERP Test',
            description: null,
            isActive: true,
        );
    }
}
