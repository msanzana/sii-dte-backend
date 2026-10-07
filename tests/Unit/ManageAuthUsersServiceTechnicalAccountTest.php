<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\UpdateAuthUserInputDto;
use App\Modules\Auth\Application\Services\ManageAuthUsersService;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Exception\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use Mockery;
use Tests\TestCase;

final class ManageAuthUsersServiceTechnicalAccountTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_show_no_expone_usuario_tecnico_oculto(): void
    {
        $repository = Mockery::mock(
            AuthUserRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn(
                $this->technicalUser(10)
            );

        $service = new ManageAuthUsersService(
            $repository
        );

        $this->expectException(
            AuthEntityNotFoundException::class
        );

        $service->show(10);
    }

    public function test_update_no_modifica_usuario_tecnico_oculto(): void
    {
        $repository = Mockery::mock(
            AuthUserRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn(
                $this->technicalUser(10)
            );

        $repository
            ->shouldNotReceive('update');

        $repository
            ->shouldNotReceive('setPassword');

        $service = new ManageAuthUsersService(
            $repository
        );

        $input = new UpdateAuthUserInputDto(
            userId: 10,
            fullName: 'Intento de modificación',
            email: 'modificado@example.com',
            password: 'Password123!'
        );

        $this->expectException(
            AuthEntityNotFoundException::class
        );

        $service->update($input);
    }

    public function test_set_active_no_bloquea_usuario_tecnico_oculto(): void
    {
        $repository = Mockery::mock(
            AuthUserRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(10)
            ->andReturn(
                $this->technicalUser(10)
            );

        $repository
            ->shouldNotReceive('setActive');

        $service = new ManageAuthUsersService(
            $repository
        );

        $this->expectException(
            AuthEntityNotFoundException::class
        );

        $service->setActive(
            10,
            false
        );
    }

    public function test_usuario_humano_visible_sigue_disponible_en_show(): void
    {
        $repository = Mockery::mock(
            AuthUserRepositoryInterface::class
        );

        $repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(
                $this->humanUser(1)
            );

        $service = new ManageAuthUsersService(
            $repository
        );

        $result = $service->show(1);

        $this->assertSame(
            1,
            $result['id']
        );

        $this->assertSame(
            'Usuario Humano',
            $result['full_name']
        );

        $this->assertSame(
            'human@example.com',
            $result['email']
        );

        $this->assertTrue(
            $result['is_active']
        );
    }

    private function technicalUser(
        int $id
    ): AuthUser {
        return new AuthUser(
            id: $id,

            fullName:
                '550e8400-e29b-41d4-a716-446655440000',

            email:
                '550e8400-e29b-41d4-a716-446655440000'
                . '@technical.invalid',

            accountType:
                AuthUser::ACCOUNT_TYPE_TECHNICAL,

            loginUuid:
                '550e8400-e29b-41d4-a716-446655440000',

            passwordHash:
                'hash-prueba',

            isActive:
                true,

            isVisible:
                false,

            lastLoginAt:
                null,
        );
    }

    private function humanUser(
        int $id
    ): AuthUser {
        return new AuthUser(
            id: $id,
            fullName: 'Usuario Humano',
            email: 'human@example.com',

            accountType:
                AuthUser::ACCOUNT_TYPE_HUMAN,

            loginUuid:
                null,

            passwordHash:
                'hash-prueba',

            isActive:
                true,

            isVisible:
                true,

            lastLoginAt:
                null,
        );
    }
}