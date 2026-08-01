<?php

namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\ImportCafInputDto;
use App\Modules\Dte\Application\UseCases\Certificate\ImportCafUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\ImportCafRequest;
use App\Modules\Dte\Presentation\Http\Resources\CafResource;
use Illuminate\Http\JsonResponse;

class CafController extends Controller
{
    public function __construct(
        private readonly ImportCafUseCase $importCafUseCase,
    ) {
    }

    public function store(ImportCafRequest $request): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('caf_file');

        if (!$file) {
            return response()->json([
                'message' => 'No se recibió el archivo CAF.',
            ], 422);
        }

        try {
            $companyId = (int) $request->attributes->get(
                'auth_company_id'
            );
            $result = $this->importCafUseCase->execute(
                new ImportCafInputDto(
                    companyId: $companyId,
                    externalSystemId: (int) $data['external_system_id'],
                    tempFilePath: $file->getRealPath(),
                    originalFilename: $file->getClientOriginalName(),
                )
            );

            return response()->json([
                'message' => 'CAF importado.',
                'data' => new CafResource((object) [
                    'id' => $result->id,
                    'companyId' => $result->companyId,
                    'dteType' => $result->dteType,
                    'folioStart' => $result->folioStart,
                    'folioEnd' => $result->folioEnd,
                    'authorizedAt' => $result->authorizedAt,
                    'cafXmlPath' => $result->cafXmlPath,
                    'isActive' => $result->isActive,
                    'externalSystemId' => $result->externalSystemId,
                    'requestedFoliosCount' => $result->requestedFoliosCount,
                    'availableFoliosCount' => $result->availableFoliosCount,
                ]),
            ], 201);

        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible importar el CAF.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
