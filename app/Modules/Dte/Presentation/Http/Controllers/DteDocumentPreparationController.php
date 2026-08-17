<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\PrepareDteDocumentForXmlInputDto;
use App\Modules\Dte\Application\UseCases\Document\PrepareDteDocumentForXmlUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\PrepareDteDocumentForXmlRequest;
use App\Modules\Dte\Presentation\Http\Resources\PreparedDteDocumentResource;
use Illuminate\Http\JsonResponse;

class DteDocumentPreparationController extends Controller
{
    public function  __construct(
        private readonly PrepareDteDocumentForXmlUseCase $prepareDocumentForXmlUseCase,
    )
    {}

    public function prepareForXml(
        PrepareDteDocumentForXmlRequest $request
    ): JsonResponse
    {
        try {
            $result = $this->prepareDocumentForXmlUseCase->execute(
                new PrepareDteDocumentForXmlInputDto(
                    documentId: (int) $request->validated('document_id')
                )
            );

            return response()->json([
                'message' => 'Documento preparado para XML.',
                'data' => new PreparedDteDocumentResource((object) [
                    'documentId' => $result->documentId,
                    'externalId' => $result->externalId,
                    'companyId' => $result->companyId,
                    'dteType' => $result->dteType,
                    'cafId' => $result->cafId,
                    'folio' => $result->folio,
                    'cafFolioStart' => $result->cafFolioStart,
                    'cafFolioEnd' => $result->cafFolioEnd,
                    'status' => $result->status,
                    'siiEnvironment'=> $result->siiEnvironment,
                ]),
            ],200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible preparar el documento para XML.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
