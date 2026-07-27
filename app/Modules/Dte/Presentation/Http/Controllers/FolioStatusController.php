<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\UseCases\Folio\ListFolioStatusesUseCase;
use Illuminate\Http\JsonResponse;

final class FolioStatusController extends Controller
{
    public function __construct(
        private readonly ListFolioStatusesUseCase $listFolioStatusesUseCase,
    ){}

    public function index(): JsonResponse
    {
        $items = $this->listFolioStatusesUseCase->execute();

        return response()->json([
            'message' => 'Estados de folio obtenidos correctamente',
            'data' => array_map(
                fn ($item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'description' => $item->description,
                    'sort_order' => $item->sortOrder,
                    'is_active' => $item->isActive,
                ],
                $items
            )
        ]);
    }
}