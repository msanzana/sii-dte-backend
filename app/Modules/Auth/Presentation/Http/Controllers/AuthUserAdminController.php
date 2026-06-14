<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\CreateAuthUserInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthUserInputDto;
use App\Modules\Auth\Application\Services\ManageAuthUsersService;
use App\Modules\Auth\Domain\Exceptions\AuthAdministrationException;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Presentation\Http\Requests\CreateAuthUserRequest;
use App\Modules\Auth\Presentation\Http\Requests\UpdateAuthUserRequest;
use Illuminate\Http\JsonResponse;

class AuthUserAdminController extends Controller
{
    public function __construct(
        private readonly ManageAuthUsersService $manageAuthUsersService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->manageAuthUsersService->list(
                isActive: request()->has('is_active')
                    ? filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                limit: (int) request()->query('limit', 100)
            ),
        ]);
    }

    public function show(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->manageAuthUsersService->show($userId),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible obtener el usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(CreateAuthUserRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'data' => $this->manageAuthUsersService->create(
                    new CreateAuthUserInputDto(
                        fullName: (string) $request->validated('full_name'),
                        email: (string) $request->validated('email'),
                        password: (string) $request->validated('password'),
                        isActive: (bool) $request->validated('is_active'),
                    )
                ),
            ], 201);
        } catch (AuthAdministrationException $e) {
            return response()->json([
                'message' => 'No fue posible crear el usuario.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateAuthUserRequest $request, int $userId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'data' => $this->manageAuthUsersService->update(
                    new UpdateAuthUserInputDto(
                        userId: $userId,
                        fullName: (string) $request->validated('full_name'),
                        email: (string) $request->validated('email'),
                        password: $request->validated('password'),
                    )
                ),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible actualizar el usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function block(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Usuario bloqueado correctamente.',
                'data' => $this->manageAuthUsersService->setActive($userId, false),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible bloquear el usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function unblock(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Usuario desbloqueado correctamente.',
                'data' => $this->manageAuthUsersService->setActive($userId, true),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible desbloquear el usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
