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
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

final class TechnicalLoginHttpTest extends TestCase
{
    private const UUID = '11111111-2222-4333-8444-555555555555';
    private const PASSWORD = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'platform_auth.jwt.secret',
            'technical-http-test-secret-not-for-production'
        );
        config()->set('platform_auth.jwt.issuer', 'sii-dte-backend-test');
        config()->set('platform_auth.jwt.audience', 'sii-dte-platform-test');
        config()->set('platform_auth.jwt.ttl_seconds.access', 3600);
        config()->set('platform_auth.refresh_tokens.ttl_seconds', 7200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_endpoint_tecnico_emite_access_y_refresh_con_ambito_resuelto_por_servidor(): void
    {
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $accesses = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $systems = Mockery::mock(ExternalSystemRepositoryInterface::class);
        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);

        $users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->with(self::UUID)
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
                id: 100,
                userId: $userId,
                companyId: $companyId,
                tokenHash: $tokenHash,
                userAgent: $userAgent,
                ipAddress: $ipAddress,
                expiresAt: $expiresAt,
                revokedAt: null,
                externalSystemId: $externalSystemId,
            ));

        $users
            ->shouldReceive('touchLastLoginAt')
            ->once()
            ->with(10);

        $this->app->instance(AuthUserRepositoryInterface::class, $users);
        $this->app->instance(TechnicalUserSystemAccessRepositoryInterface::class, $accesses);
        $this->app->instance(AuthCompanyStateRepositoryInterface::class, $companies);
        $this->app->instance(ExternalSystemRepositoryInterface::class, $systems);
        $this->app->instance(AuthRefreshTokenRepositoryInterface::class, $refreshTokens);

        $response = $this->postJson('/api/internal/auth/technical/login', [
            'login_uuid' => self::UUID,
            'password' => self::PASSWORD,
            'company_id' => 999,
            'external_system_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user_id', 10)
            ->assertJsonPath('data.account_type', AuthUser::ACCOUNT_TYPE_TECHNICAL)
            ->assertJsonPath('data.company_id', 1)
            ->assertJsonPath('data.external_system_id', 7)
            ->assertJsonPath('data.refresh_expires_in_seconds', 7200)
            ->assertJsonPath('data.token_stage', 'access');

        $this->assertNotEmpty($response->json('data.refresh_token'));

        $token = (string) $response->json('data.access_token');
        $payload = app(JwtHs256Service::class)->parseAndValidate($token);

        $this->assertSame(10, $payload['uid']);
        $this->assertSame(AuthUser::ACCOUNT_TYPE_TECHNICAL, $payload['account_type']);
        $this->assertSame(1, $payload['company_id']);
        $this->assertSame(7, $payload['external_system_id']);
        $this->assertSame('access', $payload['stage']);
    }

    public function test_endpoint_tecnico_rechaza_credenciales_invalidas_con_respuesta_generica(): void
    {
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $accesses = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $systems = Mockery::mock(ExternalSystemRepositoryInterface::class);
        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);

        $users
            ->shouldReceive('findActiveTechnicalByLoginUuid')
            ->once()
            ->with(self::UUID)
            ->andReturn(null);

        $accesses->shouldNotReceive('findActiveByUserId');
        $companies->shouldNotReceive('findById');
        $systems->shouldNotReceive('findByCompanyAndId');
        $refreshTokens->shouldNotReceive('create');
        $users->shouldNotReceive('touchLastLoginAt');

        $this->app->instance(AuthUserRepositoryInterface::class, $users);
        $this->app->instance(TechnicalUserSystemAccessRepositoryInterface::class, $accesses);
        $this->app->instance(AuthCompanyStateRepositoryInterface::class, $companies);
        $this->app->instance(ExternalSystemRepositoryInterface::class, $systems);
        $this->app->instance(AuthRefreshTokenRepositoryInterface::class, $refreshTokens);

        $response = $this->postJson('/api/internal/auth/technical/login', [
            'login_uuid' => self::UUID,
            'password' => self::PASSWORD,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'No fue posible autenticar la integración.');
    }

    public function test_endpoint_tecnico_valida_uuid_y_password(): void
    {
        $response = $this->postJson('/api/internal/auth/technical/login', [
            'login_uuid' => 'no-es-un-uuid',
            'password' => 'corta',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'login_uuid',
                'password',
            ]);
    }

    private function technicalUser(): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'technical-user',
            email: 'technical-user@technical.invalid',
            accountType: AuthUser::ACCOUNT_TYPE_TECHNICAL,
            loginUuid: self::UUID,
            passwordHash: Hash::make(self::PASSWORD),
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
