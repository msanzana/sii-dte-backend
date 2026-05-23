<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\BuildDteXmlInputDto;
use App\Modules\Dte\Application\UseCases\Document\BuildDteXmlUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\BuiltDteXmlRequest;
use App\Modules\Dte\Presentation\Http\Resources\BuiltDteXmlResource;
use Illuminate\Http\JsonResponse;

class DteXmlBuildController extends Controller
{
    public function __construct(
        private readonly BuildDteXmlUseCase $buildDteXmlUseCase
    )
    {}

    public function build(
        BuiltDteXmlRequest $request
    ): JsonResponse
    {
        try {
            $result = $this->buildDteXmlUseCase->execute(
                new BuildDteXmlInputDto(
                    documentId: (int) $request->validated('documento_id')
                )
            );
            return response()->json([
                'message' => 'XML base del documento construido.',
                'data' => new BuiltDteXmlResource((object) [
                    'documentId' => $result->documentId,
                    'externalId' => $result->externalId,
                    'companyId' => $result->companyId,
                    'dteType' => $result->dteType,
                    'folio' => $result->folio,
                    'documentXmlId' => $result->documentXmlId,
                    'status' => $result->status,
                    'siiEnvironment' => $result->siiEnvironment,
                    'unsignedXmlPath' => $result->unsignedXmlPath,
                ]),
            ],200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible construir el Xml del documento.',
                'error' => $e->getMessage(),
            ],422);
        }
    }
}
