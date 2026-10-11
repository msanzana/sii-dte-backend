<?php

namespace Tests\Feature;

use App\Modules\Auth\Domain\Entities\AuthRefreshToken;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Entities\ManagedCompany;
use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;
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

final class TechnicalRefreshHttpTest extends TestCase
{
    private const SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('platform_auth.jwt.secret', 'technical-refresh-http-test-secret');
        config()->set('platform_auth.jwt.issuer', 'sii-dte-backend-test');
        config()->set('platform_auth.jwt.audience', 'sii-dte-platform-test');
        config()->set('platform_auth.jwt.ttl_seconds.access', 3600);
        config()->set('platform_auth.refresh_tokens.ttl_seconds', 7200);
        config()->set('platform_auth.refresh_tokens.rotate_on_refresh', true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_endpoint_refresh_tecnico_rota_refresh_y_conserva_ambito(): void
    {
        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $accesses = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $systems = Mockery::mock(ExternalSystemRepositoryInterface::class);

        $refreshTokens
            ->shouldReceive('findById')
            ->once()
            ->with(50)
            ->andReturn($this->refreshToken(externalSystemId: 7));

        $users
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn($this->technicalUser());

        $accesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([$this->technicalAccess()]);

        $companies
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($this->activeCompany());

        $systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 7)
            ->andReturn($this->activeSystem());

        $refreshTokens
            ->shouldReceive('create')
            ->once()
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

        $refreshTokens
            ->shouldReceive('revoke')
            ->once()
            ->with(50);

        $this->bindRepositories($refreshTokens, $users, $accesses, $companies, $systems);

        $response = $this->postJson('/api/internal/auth/technical/refresh', [
            'refresh_token' => $this->publicRefreshToken(),
            'company_id' => 999,
            'external_system_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Sesión técnica renovada correctamente.')
            ->assertJsonPath('data.account_type', AuthUser::ACCOUNT_TYPE_TECHNICAL)
            ->assertJsonPath('data.company_id', 1)
            ->assertJsonPath('data.external_system_id', 7)
            ->assertJsonPath('data.refresh_expires_in_seconds', 7200)
            ->assertJsonPath('data.token_stage', 'access');

        $this->assertNotSame(
            $this->publicRefreshToken(),
            $response->json('data.refresh_token')
        );

        $payload = app(JwtHs256Service::class)->parseAndValidate(
            (string) $response->json('data.access_token')
        );

        $this->assertSame(AuthUser::ACCOUNT_TYPE_TECHNICAL, $payload['account_type']);
        $this->assertSame(1, $payload['company_id']);
        $this->assertSame(7, $payload['external_system_id']);
    }

    public function test_endpoint_refresh_tecnico_rechaza_refresh_humano(): void
    {
        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $accesses = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $systems = Mockery::mock(ExternalSystemRepositoryInterface::class);

        $refreshTokens
            ->shouldReceive('findById')
            ->once()
            ->with(50)
            ->andReturn($this->refreshToken(externalSystemId: null));

        $users->shouldNotReceive('findById');
        $accesses->shouldNotReceive('findActiveByUserId');
        $companies->shouldNotReceive('findById');
        $systems->shouldNotReceive('findByCompanyAndId');
        $refreshTokens->shouldNotReceive('create');
        $refreshTokens->shouldNotReceive('revoke');

        $this->bindRepositories($refreshTokens, $users, $accesses, $companies, $systems);

        $response = $this->postJson('/api/internal/auth/technical/refresh', [
            'refresh_token' => $this->publicRefreshToken(),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'No fue posible renovar la sesión técnica.');
    }

    public function test_endpoint_refresh_tecnico_exige_refresh_token(): void
    {
        $response = $this->postJson('/api/internal/auth/technical/refresh', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    private function bindRepositories(
        AuthRefreshTokenRepositoryInterface $refreshTokens,
        AuthUserRepositoryInterface $users,
        TechnicalUserSystemAccessRepositoryInterface $accesses,
        AuthCompanyStateRepositoryInterface $companies,
        ExternalSystemRepositoryInterface $systems,
    ): void {
        $this->app->instance(AuthRefreshTokenRepositoryInterface::class, $refreshTokens);
        $this->app->instance(AuthUserRepositoryInterface::class, $users);
        $this->app->instance(TechnicalUserSystemAccessRepositoryInterface::class, $accesses);
        $this->app->instance(AuthCompanyStateRepositoryInterface::class, $companies);
        $this->app->instance(ExternalSystemRepositoryInterface::class, $systems);
    }

    private function publicRefreshToken(): string
    {
        return (new RefreshTokenService())->buildPublicToken(50, self::SECRET);
    }

    private function refreshToken(?int $externalSystemId): AuthRefreshToken
    {
        return new AuthRefreshToken(
            id: 50,
            userId: 10,
            companyId: 1,
            tokenHash: hash('sha256', self::SECRET),
            userAgent: null,
            ipAddress: null,
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

    private function technicalAccess(): TechnicalUserSystemAccess
    {
        return new TechnicalUserSystemAccess(
            id: 20,
            userId: 10,
            companyId: 1,
            externalSystemId: 7,
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
