<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\AssignUserCompanyInputDto;
use App\Modules\Auth\Application\DTOs\UpdateUserCompanyAssignmentInputDto;
use App\Modules\Auth\Application\Services\ManageUserCompanyAssignmentsService;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Entities\UserCompanyAccess;
use App\Modules\Auth\Domain\Exception\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRolesRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use Mockery;
use Tests\TestCase;

final class ManageUserCompanyAssignmentsTechnicalAccountTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_by_user_oculta_las_asignaciones_del_tecnico(): void
    {
        $users = $this->usersReturning($this->technicalUser());
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses->shouldNotReceive('findAssignmentsByUserId');

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->listByUser(10);
    }

    public function test_assign_rechaza_asignar_otra_empresa_a_un_tecnico(): void
    {
        $users = $this->usersReturning($this->technicalUser());
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses->shouldNotReceive('assignCompanyToUser');

        $input = new AssignUserCompanyInputDto(
            userId: 10,
            companyId: 2,
            isDefault: false,
            canSelectCompany: false,
            roleIds: [],
        );

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->assign($input);
    }

    public function test_update_rechaza_modificar_asignacion_de_un_tecnico(): void
    {
        $users = $this->usersReturning($this->technicalUser());
        $accesses = $this->accessesReturningTechnicalAssignment();
        $accesses->shouldNotReceive('updateAssignment');

        $input = new UpdateUserCompanyAssignmentInputDto(
            accessId: 44,
            isDefault: false,
            canSelectCompany: true,
            roleIds: [],
        );

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->update($input);
    }

    public function test_set_active_rechaza_desactivar_asignacion_de_un_tecnico(): void
    {
        $users = $this->usersReturning($this->technicalUser());
        $accesses = $this->accessesReturningTechnicalAssignment();
        $accesses->shouldNotReceive('setAssignmentActive');

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->setActive(44, false);
    }

    public function test_remove_rechaza_eliminar_asignacion_de_un_tecnico(): void
    {
        $users = $this->usersReturning($this->technicalUser());
        $accesses = $this->accessesReturningTechnicalAssignment();
        $accesses->shouldNotReceive('removeAssignment');

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->remove(44);
    }

    public function test_un_tecnico_visible_por_error_tambien_es_rechazado(): void
    {
        $users = $this->usersReturning($this->technicalUser(visible: true));
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses->shouldNotReceive('findAssignmentsByUserId');

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->listByUser(10);
    }

    public function test_un_humano_oculto_tambien_es_rechazado(): void
    {
        $users = $this->usersReturning($this->humanUser(visible: false));
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses->shouldNotReceive('findAssignmentsByUserId');

        $this->expectException(AuthEntityNotFoundException::class);
        $this->service($users, $accesses)->listByUser(10);
    }

    public function test_un_humano_visible_conserva_el_acceso_al_listado(): void
    {
        $users = $this->usersReturning($this->humanUser());
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses
            ->shouldReceive('findAssignmentsByUserId')
            ->once()
            ->with(10)
            ->andReturn([]);

        $this->assertSame(
            [],
            $this->service($users, $accesses)->listByUser(10)
        );
    }

    private function usersReturning(AuthUser $user): AuthUserRepositoryInterface
    {
        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findById')->once()->with(10)->andReturn($user);
        return $users;
    }

    private function accessesReturningTechnicalAssignment(): UserCompanyAccessRepositoryInterface
    {
        $accesses = Mockery::mock(UserCompanyAccessRepositoryInterface::class);
        $accesses
            ->shouldReceive('findByAccessId')
            ->once()
            ->with(44)
            ->andReturn(new UserCompanyAccess(
                accessId: 44,
                userId: 10,
                companyId: 1,
                companyRut: '76123456-7',
                companyName: 'Empresa de prueba',
                isActive: true,
                isDefault: true,
                canSelectCompany: false,
            ));
        return $accesses;
    }

    private function service(
        AuthUserRepositoryInterface $users,
        UserCompanyAccessRepositoryInterface $accesses
    ): ManageUserCompanyAssignmentsService {
        return new ManageUserCompanyAssignmentsService(
            authUserRepository: $users,
            authCompanyStateRepository: Mockery::mock(AuthCompanyStateRepositoryInterface::class),
            authRoleRepository: Mockery::mock(AuthRolesRepositoryInterface::class),
            userCompanyAccessRepository: $accesses,
        );
    }

    private function technicalUser(bool $visible = false): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: '550e8400-e29b-41d4-a716-446655440000',
            email: '550e8400-e29b-41d4-a716-446655440000@technical.invalid',
            accountType: AuthUser::ACCOUNT_TYPE_TECHNICAL,
            loginUuid: '550e8400-e29b-41d4-a716-446655440000',
            passwordHash: 'hash-prueba',
            isActive: true,
            isVisible: $visible,
            lastLoginAt: null,
        );
    }

    private function humanUser(bool $visible = true): AuthUser
    {
        return new AuthUser(
            id: 10,
            fullName: 'Usuario Humano',
            email: 'humano@example.com',
            accountType: AuthUser::ACCOUNT_TYPE_HUMAN,
            loginUuid: null,
            passwordHash: 'hash-prueba',
            isActive: true,
            isVisible: $visible,
            lastLoginAt: null,
        );
    }
}
