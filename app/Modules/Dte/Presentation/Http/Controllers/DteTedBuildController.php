<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\BuildTedInputDto;
use App\Modules\Dte\Application\DTOs\BuildTedResultDto;
use App\Modules\Dte\Application\UseCases\Document\BuildTedUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\BuildTedRequest;
use App\Modules\Dte\Presentation\Http\Resources\BuiltTedResource;
use Illuminate\Http\JsonResponse;

class DteTedBuildController extends Controller
{
    public function __construct(
        private readonly BuildTedUseCase $buildTedUseCase,
    )
    {}

    public function build(BuildTedRequest $request): JsonResponse
    {
        try {
            $result = $this->buildTedUseCase->execute(
                new BuildTedInputDto(
                    documentId: (int) $request->validated('document_id')
                )
            );

            return response()->json([
                'message' => 'TED construido correctamente.',
                'data' => new BuiltTedResource((object) [
                    'documentId' => $result->documentId,
                    'externalId' => $result->externalId,
                    'companyId' => $result->companyId,
                    'dteType' => $result->dteType,
                    'folio' => $result->folio,
                    'cafId' => $result->cafId,
                    'status' => $result->status,
                    'unsignedXmlPath' => $result->unsignedXmlPath,
                    'tedXml' => $result->tedXml,
                ]),
            ],200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible construir el TED del documento.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}
