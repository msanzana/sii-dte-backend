<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\TechnicalLoginInputDto;
use App\Modules\Auth\Application\UseCases\TechnicalLoginUseCase;
use App\Modules\Auth\Domain\Entities\AuthRefreshToken;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Entities\ManagedCompany;
use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;
use App\Modules\Auth\Domain\Exception\InvalidCredentialsException;
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

final class TechnicalLoginUseCaseTest extends TestCase
{
    private AuthUserRepositoryInterface $users;
    private TechnicalUserSystemAccessRepositoryInterface $technicalAccesses;
    private AuthCompanyStateRepositoryInterface $companies;
    private ExternalSystemRepositoryInterface $systems;
    private AuthRefreshTokenRepositoryInterface $refreshTokens;
    private JwtHs256Service $jwt;
    private RefreshTokenService $refreshTokenService;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'platform_auth.jwt.secret',
            'technical-login-test-secret-not-for-production'
        );
        config()->set('platform_auth.jwt.issuer', 'sii-dte-backend-test');
        config()->set('platform_auth.jwt.audience', 'sii-dte-platform-test');
        config()->set('platform_auth.jwt.ttl_seconds.access', 3600);
        config()->set('platform_auth.refresh_tokens.ttl_seconds', 7200);

        $this->users = Mockery::mock(AuthUserRepositoryInterface::class);
        $this->technicalAccesses = Mockery::mock(
            TechnicalUserSystemAccessRepositoryInterface::class
        );
        $this->companies = Mockery::mock(
            AuthCompanyStateRepositoryInterface::class
        );
        $this->systems = Mockery::mock(ExternalSystemRepositoryInterface::class);
        $this->refreshTokens = Mockery::mock(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->jwt = new JwtHs256Service();
        $this->refreshTokenService = new RefreshTokenService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_tecnico_emite_jwt_y_refresh_con_ambito_resuelto_por_servidor(): void
    {
        $user = $this->technicalUser(password: 'secret-123');
        $access = $this->technicalAccess();
        $company = $this->activeCompany();
        $system = $this->activeSystem();

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->with('11111111-2222-4333-8444-555555555555')
            ->andReturn($user);

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([$access]);

        $this->companies
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($company);

        $this->systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 7)
            ->andReturn($system);

        $this->refreshTokens
            ->shouldReceive('create')
            ->once()
            ->withArgs(function (
                int $userId,
                int $companyId,
                string $tokenHash,
                string $expiresAt,
                ?string $userAgent,
                ?string $ipAddress,
                ?int $externalSystemId,
            ): bool {
                return $userId === 10
                    && $companyId === 1
                    && strlen($tokenHash) === 64
                    && $expiresAt !== ''
                    && $userAgent === 'PHPUnit'
                    && $ipAddress === '127.0.0.1'
                    && $externalSystemId === 7;
            })
            ->andReturnUsing(function (
                int $userId,
                int $companyId,
                string $tokenHash,
                string $expiresAt,
                ?string $userAgent,
                ?string $ipAddress,
                ?int $externalSystemId,
            ): AuthRefreshToken {
                return new AuthRefreshToken(
                    id: 100,
                    userId: $userId,
                    companyId: $companyId,
                    tokenHash: $tokenHash,
                    userAgent: $userAgent,
                    ipAddress: $ipAddress,
                    expiresAt: $expiresAt,
                    revokedAt: null,
                    externalSystemId: $externalSystemId,
                );
            });

        $this->users
            ->shouldReceive('touchLastLoginAt')
            ->once()
            ->with(10);

        $result = $this->useCase()->execute(
            new TechnicalLoginInputDto(
                loginUuid: '11111111-2222-4333-8444-555555555555',
                password: 'secret-123',
                userAgent: 'PHPUnit',
                ipAddress: '127.0.0.1',
            )
        );

        $this->assertSame(10, $result->userId);
        $this->assertSame(1, $result->companyId);
        $this->assertSame(7, $result->externalSystemId);
        $this->assertSame(3600, $result->accessExpiresInSeconds);
        $this->assertSame(7200, $result->refreshExpiresInSeconds);

        $payload = $this->jwt->parseAndValidate($result->accessToken);

        $this->assertSame('10', $payload['sub']);
        $this->assertSame(10, $payload['uid']);
        $this->assertSame(AuthUser::ACCOUNT_TYPE_TECHNICAL, $payload['account_type']);
        $this->assertSame('access', $payload['stage']);
        $this->assertSame(1, $payload['company_id']);
        $this->assertSame(7, $payload['external_system_id']);
        $this->assertSame([], $payload['roles']);
        $this->assertSame([], $payload['permissions']);

        $parsedRefresh = $this->refreshTokenService->parsePublicToken(
            $result->refreshToken
        );

        $this->assertSame(100, $parsedRefresh['id']);
    }

    public function test_login_tecnico_rechaza_uuid_inexistente_o_inactivo(): void
    {
        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn(null);

        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->refreshTokens->shouldNotReceive('create');
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute(
            new TechnicalLoginInputDto(
                loginUuid: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
                password: 'anything',
            )
        );
    }

    public function test_login_tecnico_rechaza_password_incorrecto(): void
    {
        $user = $this->technicalUser(password: 'correct-password');

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn($user);

        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->refreshTokens->shouldNotReceive('create');
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute(
            new TechnicalLoginInputDto(
                loginUuid: (string) $user->loginUuid(),
                password: 'wrong-password',
            )
        );
    }

    public function test_login_tecnico_rechaza_si_no_tiene_ambito_activo(): void
    {
        $user = $this->technicalUser();

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn($user);

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([]);

        $this->companies->shouldNotReceive('findById');
        $this->refreshTokens->shouldNotReceive('create');
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute($this->validInput());
    }

    public function test_login_tecnico_rechaza_si_tiene_mas_de_un_ambito_activo(): void
    {
        $user = $this->technicalUser();

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn($user);

        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([
                $this->technicalAccess(id: 20, companyId: 1, systemId: 7),
                $this->technicalAccess(id: 21, companyId: 1, systemId: 8),
            ]);

        $this->companies->shouldNotReceive('findById');
        $this->refreshTokens->shouldNotReceive('create');
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute($this->validInput());
    }

    public function test_login_tecnico_rechaza_empresa_inactiva(): void
    {
        $user = $this->technicalUser();

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn($user);

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
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute($this->validInput());
    }

    public function test_login_tecnico_rechaza_sistema_inactivo(): void
    {
        $user = $this->technicalUser();

        $this->users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->andReturn($user);

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
        $this->users->shouldNotReceive('touchLastLoginAt');

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase()->execute($this->validInput());
    }

    private function useCase(): TechnicalLoginUseCase
    {
        return new TechnicalLoginUseCase(
            authUserRepository: $this->users,
            technicalUserSystemAccessRepository: $this->technicalAccesses,
            companyStateRepository: $this->companies,
            externalSystemRepository: $this->systems,
            authRefreshTokenRepository: $this->refreshTokens,
            jwtHs256Service: $this->jwt,
            refreshTokenService: $this->refreshTokenService,
        );
    }

    private function validInput(): TechnicalLoginInputDto
    {
        return new TechnicalLoginInputDto(
            loginUuid: '11111111-2222-4333-8444-555555555555',
            password: 'secret-123',
        );
    }

    private function technicalUser(string $password = 'secret-123'): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'technical-user',
            email: 'technical-user@technical.invalid',
            accountType: AuthUser::ACCOUNT_TYPE_TECHNICAL,
            loginUuid: '11111111-2222-4333-8444-555555555555',
            passwordHash: Hash::make($password),
            isActive: true,
            isVisible: false,
            lastLoginAt: null,
        );
    }

    private function technicalAccess(
        int $id = 20,
        int $companyId = 1,
        int $systemId = 7,
    ): TechnicalUserSystemAccess {
        return new TechnicalUserSystemAccess(
            id: $id,
            userId: 10,
            companyId: $companyId,
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
