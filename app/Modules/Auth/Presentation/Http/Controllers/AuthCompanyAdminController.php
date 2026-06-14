<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\Services\ManageCompaniesStateService;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use Illuminate\Http\JsonResponse;

class AuthCompanyAdminController extends Controller
{
    public function __construct(
        private readonly ManageCompaniesStateService $manageCompaniesStateService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->manageCompaniesStateService->list(
                isActive: request()->has('is_active')
                    ? filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : null,
                limit: (int) request()->query('limit', 100)
            ),
        ]);
    }

    public function show(int $companyId): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->manageCompaniesStateService->show($companyId),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible obtener la empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function block(int $companyId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Empresa bloqueada correctamente.',
                'data' => $this->manageCompaniesStateService->setActive($companyId, false),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible bloquear la empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function unblock(int $companyId): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Empresa desbloqueada correctamente.',
                'data' => $this->manageCompaniesStateService->setActive($companyId, true),
            ]);
        } catch (AuthEntityNotFoundException $e) {
            return response()->json([
                'message' => 'No fue posible desbloquear la empresa.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
