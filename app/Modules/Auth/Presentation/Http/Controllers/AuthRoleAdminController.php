<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\CreateAuthRoleInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthRoleInputDto;
use App\Modules\Auth\Application\Services\ManageAuthRolesService;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Presentation\Http\Requests\CreateAuthRoleRequest;
use App\Modules\Auth\Presentation\Http\Requests\SyncRolePermissionsRequest;
use App\Modules\Auth\Presentation\Http\Requests\UpdateAuthRoleRequest;
use Illuminate\Http\JsonResponse;

class AuthRoleAdminController extends Controller
{
    public function __construct(
        private readonly ManageAuthRolesService $manageAuthRolesService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->manageAuthRolesService->list(
                isActive: request()->has('is_active')
                    ? filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                limit: (int) request()->query('limit', 100)
            ),
        ]);
    }

    public function show(int $roleId): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->manageAuthRolesService->show($roleId),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible obtener el rol.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(CreateAuthRoleRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Rol creado correctamente.',
            'data' => $this->manageAuthRolesService->create(
                new CreateAuthRoleInputDto(
                    code: (string) $request->validated('code'),
                    name: (string) $request->validated('name'),
                    description: $request->validated('description'),
                    isSystem: (bool) $request->validated('is_system'),
                    isActive: (bool) $request->validated('is_active'),
                )
            ),
        ], 201);
    }

    public function update(UpdateAuthRoleRequest $request, int $roleId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Rol actualizado correctamente.',
                'data' => $this->manageAuthRolesService->update(
                    new UpdateAuthRoleInputDto(
                        roleId: $roleId,
                        code: (string) $request->validated('code'),
                        name: (string) $request->validated('name'),
                        description: $request->validated('description'),
                    )
                ),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible actualizar el rol.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, int $roleId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Permisos del rol actualizados correctamente.',
                'data' => $this->manageAuthRolesService->syncPermissions(
                    roleId: $roleId,
                    permissionIds: array_map(fn ($id) => (int) $id, $request->validated('permission_ids'))
                ),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible sincronizar permisos del rol.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function block(int $roleId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Rol bloqueado correctamente.',
                'data' => $this->manageAuthRolesService->setActive($roleId, false),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible bloquear el rol.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function unblock(int $roleId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Rol desbloqueado correctamente.',
                'data' => $this->manageAuthRolesService->setActive($roleId, true),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible desbloquear el rol.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
