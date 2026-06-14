<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\AssignUserCompanyInputDto;
use App\Modules\Auth\Application\DTOs\UpdateUserCompanyAssignmentInputDto;
use App\Modules\Auth\Application\Services\ManageUserCompanyAssignmentsService;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Presentation\Http\Requests\AssignUserCompanyRequest;
use App\Modules\Auth\Presentation\Http\Requests\UpdateUserCompanyAssignmentRequest;
use Illuminate\Http\JsonResponse;

class AuthUserCompanyAccessAdminController extends Controller
{
    public function __construct(
        private readonly ManageUserCompanyAssignmentsService $manageUserCompanyAssignmentsService,
    ) {
    }

    public function index(int $userId): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->manageUserCompanyAssignmentsService->listByUser($userId),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible obtener las asignaciones del usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(AssignUserCompanyRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Empresa asignada al usuario correctamente.',
                'data' => $this->manageUserCompanyAssignmentsService->assign(
                    new AssignUserCompanyInputDto(
                        userId: (int) $request->validated('user_id'),
                        companyId: (int) $request->validated('company_id'),
                        isDefault: (bool) $request->validated('is_default'),
                        canSelectCompany: (bool) $request->validated('can_select_company'),
                        roleIds: array_map(fn ($id) => (int) $id, $request->validated('role_ids')),
                    )
                ),
            ], 201);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible asignar la empresa al usuario.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(UpdateUserCompanyAssignmentRequest $request, int $accessId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Asignación usuario-empresa actualizada correctamente.',
                'data' => $this->manageUserCompanyAssignmentsService->update(
                    new UpdateUserCompanyAssignmentInputDto(
                        accessId: $accessId,
                        isDefault: (bool) $request->validated('is_default'),
                        canSelectCompany: (bool) $request->validated('can_select_company'),
                        roleIds: array_map(fn ($id) => (int) $id, $request->validated('role_ids')),
                    )
                ),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible actualizar la asignación usuario-empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function block(int $accessId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Acceso usuario-empresa bloqueado correctamente.',
                'data' => $this->manageUserCompanyAssignmentsService->setActive($accessId, false),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible bloquear el acceso usuario-empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function unblock(int $accessId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Acceso usuario-empresa desbloqueado correctamente.',
                'data' => $this->manageUserCompanyAssignmentsService->setActive($accessId, true),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible desbloquear el acceso usuario-empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function destroy(int $accessId): JsonResponse
    {
        try {
            $this->manageUserCompanyAssignmentsService->remove($accessId);

            return response()->json([
                'message' => 'Asignación usuario-empresa eliminada correctamente.',
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible eliminar la asignación usuario-empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
