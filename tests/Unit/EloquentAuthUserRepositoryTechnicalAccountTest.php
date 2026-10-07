<?php

namespace Tests\Unit;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Infrastructure\Persistence\Repositories\EloquentAuthUserRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EloquentAuthUserRepositoryTechnicalAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('auth_users');

        Schema::create(
            'auth_users',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'full_name',
                    150
                );

                $table->string(
                    'email',
                    150
                )->unique();

                $table->string(
                    'account_type',
                    20
                )->default('human');

                $table->uuid(
                    'login_uuid'
                )->nullable()->unique();

                $table->string(
                    'password_hash',
                    255
                );

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->boolean(
                    'is_visible'
                )->default(true);

                $table->timestamp(
                    'last_login_at'
                )->nullable();

                $table->timestamps();
            }
        );
    }

    public function test_find_all_oculta_usuarios_no_visibles(): void
    {
        $humanId = $this->insertUser([
            'full_name' => 'Usuario Humano',
            'email' => 'human@example.com',
            'account_type' =>
                AuthUser::ACCOUNT_TYPE_HUMAN,
            'is_visible' => true,
        ]);

        $this->insertUser([
            'full_name' => 'Usuario Técnico',
            'email' =>
                'technical@example.invalid',
            'account_type' =>
                AuthUser::ACCOUNT_TYPE_TECHNICAL,
            'login_uuid' =>
                '550e8400-e29b-41d4-a716-446655440000',
            'is_visible' => false,
        ]);

        $repository =
            new EloquentAuthUserRepository();

        $users = $repository->findAll();

        $this->assertCount(
            1,
            $users
        );

        $this->assertSame(
            $humanId,
            $users[0]->id()
        );

        $this->assertTrue(
            $users[0]->isVisible()
        );
    }

    public function test_login_por_email_solo_encuentra_usuarios_humanos(): void
    {
        $this->insertUser([
            'full_name' => 'Usuario Humano',
            'email' => 'human@example.com',
            'account_type' =>
                AuthUser::ACCOUNT_TYPE_HUMAN,
        ]);

        $this->insertUser([
            'full_name' => 'Usuario Técnico',
            'email' =>
                'technical@example.invalid',
            'account_type' =>
                AuthUser::ACCOUNT_TYPE_TECHNICAL,
            'login_uuid' =>
                '550e8400-e29b-41d4-a716-446655440000',
            'is_visible' => false,
        ]);

        $repository =
            new EloquentAuthUserRepository();

        $human = $repository
            ->findActiveByEmail(
                'human@example.com'
            );

        $technicalByEmail = $repository
            ->findActiveByEmail(
                'technical@example.invalid'
            );

        $this->assertNotNull(
            $human
        );

        $this->assertTrue(
            $human->isHuman()
        );

        $this->assertNull(
            $technicalByEmail
        );
    }

    public function test_login_tecnico_busca_por_uuid_y_tipo_technical(): void
    {
        $uuid =
            '550e8400-e29b-41d4-a716-446655440000';

        $technicalId = $this->insertUser([
            'full_name' => $uuid,

            'email' =>
                $uuid . '@technical.invalid',

            'account_type' =>
                AuthUser::ACCOUNT_TYPE_TECHNICAL,

            'login_uuid' => $uuid,
            'is_visible' => false,
        ]);

        $repository =
            new EloquentAuthUserRepository();

        $user = $repository
            ->findActiveTechnicalByLoginUuid(
                $uuid
            );

        $this->assertNotNull(
            $user
        );

        $this->assertSame(
            $technicalId,
            $user->id()
        );

        $this->assertSame(
            $uuid,
            $user->loginUuid()
        );

        $this->assertTrue(
            $user->isTechnical()
        );

        $this->assertFalse(
            $user->isVisible()
        );
    }

    public function test_login_tecnico_no_acepta_usuario_tecnico_inactivo(): void
    {
        $uuid =
            '550e8400-e29b-41d4-a716-446655440001';

        $this->insertUser([
            'full_name' => $uuid,

            'email' =>
                $uuid . '@technical.invalid',

            'account_type' =>
                AuthUser::ACCOUNT_TYPE_TECHNICAL,

            'login_uuid' => $uuid,
            'is_active' => false,
            'is_visible' => false,
        ]);

        $repository =
            new EloquentAuthUserRepository();

        $user = $repository
            ->findActiveTechnicalByLoginUuid(
                $uuid
            );

        $this->assertNull(
            $user
        );
    }

    public function test_create_existente_sigue_creando_usuario_humano_visible(): void
    {
        $repository =
            new EloquentAuthUserRepository();

        $user = $repository->create(
            fullName: 'Nuevo Usuario',
            email: 'nuevo@example.com',
            passwordHash: 'hash-prueba',
            isActive: true,
        );

        $this->assertTrue(
            $user->isHuman()
        );

        $this->assertNull(
            $user->loginUuid()
        );

        $this->assertTrue(
            $user->isVisible()
        );

        $this->assertTrue(
            $user->isActive()
        );
    }

    private function insertUser(
        array $overrides = []
    ): int {
        return (int) DB::table(
            'auth_users'
        )->insertGetId(
            array_merge(
                [
                    'full_name' =>
                        'Usuario Prueba',

                    'email' =>
                        uniqid(
                            'user-',
                            true
                        ) . '@example.com',

                    'account_type' =>
                        AuthUser::ACCOUNT_TYPE_HUMAN,

                    'login_uuid' => null,

                    'password_hash' =>
                        'hash-prueba',

                    'is_active' => true,
                    'is_visible' => true,

                    'last_login_at' => null,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $overrides
            )
        );
    }
}