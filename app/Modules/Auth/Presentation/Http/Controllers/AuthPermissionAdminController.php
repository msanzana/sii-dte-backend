<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\CreateAuthPermissionInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthPermissionInputDto;
use App\Modules\Auth\Application\Services\ManageAuthPermissionsService;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Presentation\Http\Requests\CreateAuthPermissionRequest;
use App\Modules\Auth\Presentation\Http\Requests\UpdateAuthPermissionRequest;
use Illuminate\Http\JsonResponse;

class AuthPermissionAdminController extends Controller
{
    public function __construct(
        private readonly ManageAuthPermissionsService $manageAuthPermissionsService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->manageAuthPermissionsService->list(
                isActive: request()->has('is_active')
                    ? filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                limit: (int) request()->query('limit', 100)
            ),
        ]);
    }

    public function show(int $permissionId): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->manageAuthPermissionsService->show($permissionId),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible obtener el permiso.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(CreateAuthPermissionRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Permiso creado correctamente.',
            'data' => $this->manageAuthPermissionsService->create(
                new CreateAuthPermissionInputDto(
                    code: (string) $request->validated('code'),
                    name: (string) $request->validated('name'),
                    module: (string) $request->validated('module'),
                    description: $request->validated('description'),
                    isActive: (bool) $request->validated('is_active'),
                )
            ),
        ], 201);
    }

    public function update(UpdateAuthPermissionRequest $request, int $permissionId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Permiso actualizado correctamente.',
                'data' => $this->manageAuthPermissionsService->update(
                    new UpdateAuthPermissionInputDto(
                        permissionId: $permissionId,
                        code: (string) $request->validated('code'),
                        name: (string) $request->validated('name'),
                        module: (string) $request->validated('module'),
                        description: $request->validated('description'),
                    )
                ),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible actualizar el permiso.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function block(int $permissionId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Permiso bloqueado correctamente.',
                'data' => $this->manageAuthPermissionsService->setActive($permissionId, false),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible bloquear el permiso.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function unblock(int $permissionId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Permiso desbloqueado correctamente.',
                'data' => $this->manageAuthPermissionsService->setActive($permissionId, true),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible desbloquear el permiso.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
