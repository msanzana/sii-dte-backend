<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\CreateExternalSystemInputDto;
use App\Modules\Dte\Application\UseCases\ExternalSystem\CreateExternalSystemUseCase;
use App\Modules\Dte\Application\UseCases\ExternalSystem\ListExternalSystemUseCase;
use App\Modules\Dte\Presentation\Http\Requests\StoreExternalSystemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ExternalSystemController extends Controller
{
    public function __construct(
        private readonly CreateExternalSystemUseCase $createExternalSystemUseCase,
        private readonly ListExternalSystemUseCase $listExternalSystemUseCase,
    ){}

    public function index(Request $request):JsonResponse
    {
        $companyId = (int) $request->attributes->get(
            'auth_company_id'
        );
        $items = $this->listExternalSystemUseCase->execute(
            $companyId
        );

        return response()->json([
            'message' => 'Sistemas externos obtenidos correctamente.',
            'data' => array_map(
                fn($item) => [
                    'id' => $item->id,
                    'company_id' => $item->companyId,
                    'code' => $item->code,
                    'name' => $item->name,
                    'description' => $item->description,
                    'is_active' => $item->isActive,
                ],
                $items
            ),
        ]);
    }

    public function store(
        StoreExternalSystemRequest $request
    ): JsonResponse
    {
        $companyId = (int) $request->attributes->get(
            'auth_company_id'
        );
        try {
            $result = $this->createExternalSystemUseCase->execute(
                new CreateExternalSystemInputDto(
                    companyId: $companyId,
                    code: (string) $request->validated('code'),
                    name: (string) $request->validated('name'),
                    description: $request->validated('description') !== null
                        ? (string) $request->validated('description')
                        : null,
                    isActive:
                        (bool) (
                            $request->validated('is_active')
                            ?? true
                        ),
                )
            );
            return response()->json([
                'message' => 'Sistema externo creado correctamente.',
                'data' => [
                    'id' => $result->id,
                    'company_id' => $result->companyId,
                    'code' => $result->code,
                    'name' => $result->name,
                    'description' => $result->description,
                    'is_active' => $result->isActive,
                ],
            ],200);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => 'No fue posible crear el sistema externo.',
                'error' => $exception->getMessage(),
            ],422);
        }
    }
}
