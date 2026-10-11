<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureJwtAccessToken;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Entities\ManagedCompany;
use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;
use App\Modules\Auth\Domain\Entities\UserCompanyAccess;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

final class EnsureJwtAccessTokenTechnicalAccountTest extends TestCase
{
    private AuthUserRepositoryInterface $users;
    private AuthCompanyStateRepositoryInterface $companies;
    private UserCompanyAccessRepositoryInterface $companyAccesses;
    private TechnicalUserSystemAccessRepositoryInterface $technicalAccesses;
    private ExternalSystemRepositoryInterface $systems;
    private JwtHs256Service $jwt;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'platform_auth.jwt.secret',
            'middleware-technical-test-secret-not-for-production'
        );
        config()->set('platform_auth.jwt.issuer', 'sii-dte-backend-test');
        config()->set('platform_auth.jwt.audience', 'sii-dte-platform-test');

        $this->users = Mockery::mock(AuthUserRepositoryInterface::class);
        $this->companies = Mockery::mock(AuthCompanyStateRepositoryInterface::class);
        $this->companyAccesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $this->technicalAccesses = Mockery::mock(
            TechnicalUserSystemAccessRepositoryInterface::class
        );
        $this->systems = Mockery::mock(ExternalSystemRepositoryInterface::class);
        $this->jwt = new JwtHs256Service();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_token_tecnico_valido_revalida_ambito_y_expone_contexto_tecnico(): void
    {
        $this->users->shouldReceive('findById')->once()->with(10)->andReturn(
            $this->technicalUser()
        );
        $this->companies->shouldReceive('findById')->once()->with(1)->andReturn(
            $this->company()
        );
        $this->companyAccesses
            ->shouldReceive('findAccessibleCompanyByUserAndCompany')
            ->once()
            ->with(10, 1)
            ->andReturn($this->companyAccess());
        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->with(10)
            ->andReturn([$this->technicalAccess()]);
        $this->systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->with(1, 7)
            ->andReturn($this->system());

        $request = $this->requestWithToken($this->technicalToken());

        $response = $this->middleware()->handle(
            $request,
            static fn (Request $request) => response()->json([
                'account_type' => $request->attributes->get('auth_account_type'),
                'company_id' => $request->attributes->get('auth_company_id'),
                'external_system_id' => $request->attributes->get('auth_external_system_id'),
                'roles' => $request->attributes->get('auth_roles'),
                'permissions' => $request->attributes->get('auth_permissions'),
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame(AuthUser::ACCOUNT_TYPE_TECHNICAL, $data['account_type']);
        $this->assertSame(1, $data['company_id']);
        $this->assertSame(7, $data['external_system_id']);
        $this->assertSame([], $data['roles']);
        $this->assertSame([], $data['permissions']);
    }

    public function test_token_tecnico_rechaza_external_system_distinto_del_acceso_vigente(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn($this->technicalUser());
        $this->companies->shouldReceive('findById')->once()->andReturn($this->company());
        $this->companyAccesses
            ->shouldReceive('findAccessibleCompanyByUserAndCompany')
            ->once()
            ->andReturn($this->companyAccess());
        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->andReturn([$this->technicalAccess(systemId: 7)]);
        $this->systems->shouldNotReceive('findByCompanyAndId');

        $response = $this->middleware()->handle(
            $this->requestWithToken($this->technicalToken(systemId: 8)),
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_token_tecnico_rechaza_si_sistema_esta_inactivo(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn($this->technicalUser());
        $this->companies->shouldReceive('findById')->once()->andReturn($this->company());
        $this->companyAccesses
            ->shouldReceive('findAccessibleCompanyByUserAndCompany')
            ->once()
            ->andReturn($this->companyAccess());
        $this->technicalAccesses
            ->shouldReceive('findActiveByUserId')
            ->once()
            ->andReturn([$this->technicalAccess()]);
        $this->systems
            ->shouldReceive('findByCompanyAndId')
            ->once()
            ->andReturn($this->system(isActive: false));

        $response = $this->middleware()->handle(
            $this->requestWithToken($this->technicalToken()),
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_token_tecnico_rechaza_si_no_declara_external_system_id(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn($this->technicalUser());
        $this->companies->shouldNotReceive('findById');
        $this->companyAccesses->shouldNotReceive('findAccessibleCompanyByUserAndCompany');
        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->systems->shouldNotReceive('findByCompanyAndId');

        $token = $this->jwt->issue([
            'sub' => '10',
            'uid' => 10,
            'account_type' => AuthUser::ACCOUNT_TYPE_TECHNICAL,
            'stage' => 'access',
            'company_id' => 1,
            'roles' => [],
            'permissions' => [],
        ], 3600);

        $response = $this->middleware()->handle(
            $this->requestWithToken($token),
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_jwt_humano_legacy_sin_account_type_sigue_funcionando(): void
    {
        $this->users->shouldReceive('findById')->once()->with(5)->andReturn(
            $this->humanUser()
        );
        $this->companies->shouldReceive('findById')->once()->with(1)->andReturn(
            $this->company()
        );
        $this->companyAccesses
            ->shouldReceive('findAccessibleCompanyByUserAndCompany')
            ->once()
            ->with(5, 1)
            ->andReturn($this->humanCompanyAccess());
        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->systems->shouldNotReceive('findByCompanyAndId');

        $token = $this->jwt->issue([
            'sub' => '5',
            'uid' => 5,
            'email' => 'human@example.test',
            'stage' => 'access',
            'company_id' => 1,
            'roles' => ['owner'],
            'permissions' => ['auth.users.manage'],
        ], 3600);

        $request = $this->requestWithToken($token);
        $response = $this->middleware()->handle(
            $request,
            static fn (Request $request) => response()->json([
                'account_type' => $request->attributes->get('auth_account_type'),
                'external_system_id' => $request->attributes->get('auth_external_system_id'),
                'roles' => $request->attributes->get('auth_roles'),
                'permissions' => $request->attributes->get('auth_permissions'),
            ])
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame(AuthUser::ACCOUNT_TYPE_HUMAN, $data['account_type']);
        $this->assertNull($data['external_system_id']);
        $this->assertSame(['owner'], $data['roles']);
        $this->assertSame(['auth.users.manage'], $data['permissions']);
    }

    public function test_jwt_humano_rechaza_external_system_id_inyectado(): void
    {
        $this->users->shouldReceive('findById')->once()->andReturn($this->humanUser());
        $this->companies->shouldNotReceive('findById');
        $this->companyAccesses->shouldNotReceive('findAccessibleCompanyByUserAndCompany');
        $this->technicalAccesses->shouldNotReceive('findActiveByUserId');
        $this->systems->shouldNotReceive('findByCompanyAndId');

        $token = $this->jwt->issue([
            'sub' => '5',
            'uid' => 5,
            'stage' => 'access',
            'company_id' => 1,
            'external_system_id' => 7,
        ], 3600);

        $response = $this->middleware()->handle(
            $this->requestWithToken($token),
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    private function middleware(): EnsureJwtAccessToken
    {
        return new EnsureJwtAccessToken(
            jwtHs256Service: $this->jwt,
            authUserRepository: $this->users,
            companyStateRepository: $this->companies,
            userCompanyAccessRepository: $this->companyAccesses,
            technicalUserSystemAccessRepository: $this->technicalAccesses,
            externalSystemRepository: $this->systems,
        );
    }

    private function requestWithToken(string $token): Request
    {
        return Request::create(
            '/internal/dte/document',
            'POST',
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );
    }

    private function technicalToken(int $systemId = 7): string
    {
        return $this->jwt->issue([
            'sub' => '10',
            'uid' => 10,
            'account_type' => AuthUser::ACCOUNT_TYPE_TECHNICAL,
            'stage' => 'access',
            'company_id' => 1,
            'external_system_id' => $systemId,
            'roles' => ['should-not-be-trusted'],
            'permissions' => ['should-not-be-trusted'],
        ], 3600);
    }

    private function technicalUser(): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'technical-user',
            email: 'technical-user@technical.invalid',
            accountType: AuthUser::ACCOUNT_TYPE_TECHNICAL,
            loginUuid: '11111111-2222-4333-8444-555555555555',
            passwordHash: 'hash',
            isActive: true,
            isVisible: false,
            lastLoginAt: null,
        );
    }

    private function humanUser(): AuthUser
    {
        return new AuthUser(
            id: 5,
            fullName: 'Human User',
            email: 'human@example.test',
            accountType: AuthUser::ACCOUNT_TYPE_HUMAN,
            loginUuid: null,
            passwordHash: 'hash',
            isActive: true,
            isVisible: true,
            lastLoginAt: null,
        );
    }

    private function company(): ManagedCompany
    {
        return new ManagedCompany(
            id: 1,
            rut: '76000000-0',
            legalName: 'Empresa Test',
            isActive: true,
        );
    }

    private function companyAccess(): UserCompanyAccess
    {
        return new UserCompanyAccess(
            accessId: 30,
            userId: 10,
            companyId: 1,
            companyRut: '76000000-0',
            companyName: 'Empresa Test',
            isActive: true,
            isDefault: true,
            canSelectCompany: false,
            roleCodes: ['accidental-role'],
            permissionCodes: ['accidental-permission'],
            permissionItems: [],
        );
    }

    private function humanCompanyAccess(): UserCompanyAccess
    {
        return new UserCompanyAccess(
            accessId: 31,
            userId: 5,
            companyId: 1,
            companyRut: '76000000-0',
            companyName: 'Empresa Test',
            isActive: true,
            isDefault: true,
            canSelectCompany: false,
            roleCodes: ['owner'],
            permissionCodes: ['auth.users.manage'],
            permissionItems: [],
        );
    }

    private function technicalAccess(
        int $companyId = 1,
        int $systemId = 7,
    ): TechnicalUserSystemAccess {
        return new TechnicalUserSystemAccess(
            id: 20,
            userId: 10,
            companyId: $companyId,
            externalSystemId: $systemId,
            isActive: true,
        );
    }

    private function system(bool $isActive = true): ExternalSystem
    {
        return new ExternalSystem(
            id: 7,
            companyId: 1,
            code: 'ERP-1',
            name: 'ERP Test',
            description: null,
            isActive: $isActive,
        );
    }
}
