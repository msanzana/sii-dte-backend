<?php

namespace Tests\Feature;

use App\Modules\Auth\Application\Services\CreateTechnicalUserService;
use App\Modules\Auth\Domain\Exception\AuthAdministrationException;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class CreateTechnicalUserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Usa la conexion sqlite :memory: definida en phpunit.xml.
        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->createMinimalSchema();

        DB::table('companies')->insert([
            ['id' => 1, 'rut' => '76111111-1', 'legal_name' => 'Empresa Uno', 'is_active' => 1],
            ['id' => 2, 'rut' => '76222222-2', 'legal_name' => 'Empresa Dos', 'is_active' => 1],
        ]);

        DB::table('external_systems')->insert([
            ['id' => 1, 'company_id' => 1, 'code' => 'S1', 'name' => 'Sistema Uno', 'is_active' => 1],
            ['id' => 2, 'company_id' => 2, 'code' => 'S2', 'name' => 'Sistema Dos', 'is_active' => 1],
        ]);
    }

    public function test_crea_usuario_tecnico_oculto_y_asignado_al_ambito_correcto(): void
    {
        $result = app(CreateTechnicalUserService::class)->execute(1, 1);

        $this->assertTrue(Str::isUuid($result->loginUuid));
        $this->assertSame(64, strlen($result->plainPassword));
        $this->assertSame(1, $result->companyId);
        $this->assertSame(1, $result->externalSystemId);

        $user = DB::table('auth_users')->where('id', $result->userId)->first();
        $this->assertNotNull($user);
        $this->assertSame('technical', $user->account_type);
        $this->assertSame(0, (int) $user->is_visible);
        $this->assertSame(1, (int) $user->is_active);
        $this->assertSame($result->loginUuid, $user->login_uuid);
        $this->assertSame($result->loginUuid, $user->full_name);
        $this->assertSame($result->loginUuid . '@technical.invalid', $user->email);
        $this->assertNotSame($result->plainPassword, $user->password_hash);
        $this->assertTrue(Hash::check($result->plainPassword, $user->password_hash));

        $companyAccess = DB::table('auth_user_company_accesses')->where('user_id', $user->id)->first();
        $this->assertNotNull($companyAccess);
        $this->assertSame(1, (int) $companyAccess->company_id);
        $this->assertSame(1, (int) $companyAccess->is_default);
        $this->assertSame(0, (int) $companyAccess->can_select_company);
        $this->assertSame(1, (int) $companyAccess->is_active);

        $systemAccess = DB::table('auth_user_external_system_accesses')->where('user_id', $user->id)->first();
        $this->assertNotNull($systemAccess);
        $this->assertSame(1, (int) $systemAccess->company_id);
        $this->assertSame(1, (int) $systemAccess->external_system_id);
        $this->assertSame(1, (int) $systemAccess->is_active);
        $this->assertSame(0, DB::table('auth_user_company_roles')->count());
    }

    public function test_rechaza_empresa_inactiva_sin_crear_usuario(): void
    {
        DB::table('companies')->where('id', 1)->update(['is_active' => 0]);

        try {
            app(CreateTechnicalUserService::class)->execute(1, 1);
            $this->fail('Se esperaba el rechazo de la empresa inactiva.');
        } catch (AuthAdministrationException $e) {
            $this->assertStringContainsString('empresa', $e->getMessage());
            $this->assertSame(0, DB::table('auth_users')->count());
        }
    }

    public function test_rechaza_sistema_de_otra_empresa_sin_crear_usuario(): void
    {
        try {
            app(CreateTechnicalUserService::class)->execute(1, 2);
            $this->fail('Se esperaba rechazo por sistema de otra empresa.');
        } catch (AuthAdministrationException $e) {
            $this->assertSame(0, DB::table('auth_users')->count());
        }
    }

    public function test_rechaza_sistema_inactivo_sin_crear_usuario(): void
    {
        DB::table('external_systems')->where('id', 1)->update(['is_active' => 0]);

        try {
            app(CreateTechnicalUserService::class)->execute(1, 1);
            $this->fail('Se esperaba el rechazo del sistema inactivo.');
        } catch (AuthAdministrationException $e) {
            $this->assertSame(0, DB::table('auth_users')->count());
        }
    }

    public function test_si_falla_asignacion_del_sistema_retrocede_usuario_y_acceso_empresa(): void
    {
        $stub = Mockery::mock(TechnicalUserSystemAccessRepositoryInterface::class);
        $stub->shouldReceive('grant')->once()->andThrow(new RuntimeException('fallo simulado'));
        $this->app->instance(TechnicalUserSystemAccessRepositoryInterface::class, $stub);

        try {
            app(CreateTechnicalUserService::class)->execute(1, 1);
            $this->fail('Se esperaba que el fallo simulado propagase la excepcion.');
        } catch (RuntimeException $e) {
            $this->assertSame('fallo simulado', $e->getMessage());
            $this->assertSame(0, DB::table('auth_users')->count());
            $this->assertSame(0, DB::table('auth_user_company_accesses')->count());
            $this->assertSame(0, DB::table('auth_user_external_system_accesses')->count());
        }
    }

    private function createMinimalSchema(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('rut');
            $t->string('legal_name');
            $t->boolean('is_active')->default(true);
        });

        Schema::create('auth_users', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->string('email')->unique();
            $t->string('account_type')->default('human');
            $t->uuid('login_uuid')->nullable()->unique();
            $t->string('password_hash');
            $t->boolean('is_active')->default(true);
            $t->boolean('is_visible')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
        });

        Schema::create('external_systems', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id');
            $t->string('code');
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['id', 'company_id']);
        });

        Schema::create('auth_user_company_accesses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
            $t->foreignId('company_id');
            $t->boolean('is_active')->default(true);
            $t->boolean('is_default')->default(false);
            $t->boolean('can_select_company')->default(false);
            $t->timestamps();
            $t->unique(['user_id', 'company_id']);
        });

        Schema::create('auth_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->boolean('is_active')->default(true);
        });

        Schema::create('auth_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->boolean('is_active')->default(true);
        });

        Schema::create('auth_user_company_roles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_company_access_id');
            $t->foreignId('role_id');
        });

        Schema::create('auth_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id');
            $t->foreignId('permission_id');
        });

        Schema::create('auth_user_external_system_accesses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id');
            $t->foreignId('company_id');
            $t->foreignId('external_system_id');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->foreign(['user_id', 'company_id'])
                ->references(['user_id', 'company_id'])->on('auth_user_company_accesses');
            $t->foreign(['external_system_id', 'company_id'])
                ->references(['id', 'company_id'])->on('external_systems');
        });
    }
}
