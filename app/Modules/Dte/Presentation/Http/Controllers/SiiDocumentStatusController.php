<?php

namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\QuerySiiDocumentStatusInputDto;
use App\Modules\Dte\Application\UseCases\Document\QuerySiiDocumentStatusUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\QuerySiiDocumentStatusRequest;
use App\Modules\Dte\Presentation\Http\Resources\QueriedSiiDocumentStatusResource;
use Illuminate\Http\JsonResponse;

class SiiDocumentStatusController extends Controller
{
    public function __construct(
        private readonly QuerySiiDocumentStatusUseCase $querySiiDocumentStatusUseCase,
    ) {
    }

    public function query(QuerySiiDocumentStatusRequest $request): JsonResponse
    {
        try {
            $result = $this->querySiiDocumentStatusUseCase->execute(
                new QuerySiiDocumentStatusInputDto(
                    documentId: (int) $request->validated('document_id')
                )
            );

            return response()->json([
                'message' => 'Consulta de estado individual ejecutada.',
                'data' => new QueriedSiiDocumentStatusResource((object) [
                    'documentId' => $result->documentId,
                    'externalId' => $result->externalId,
                    'companyId' => $result->companyId,
                    'dteType' => $result->dteType,
                    'folio' => $result->folio,
                    'queriedVia' => $result->queriedVia,
                    'siiStatusCode' => $result->siiStatusCode,
                    'siiStatusMessage' => $result->siiStatusMessage,
                    'attentionNumber' => $result->attentionNumber,
                    'internalStatus' => $result->internalStatus,
                    'rawBody' => $result->rawBody,
                ]),
            ], 200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible consultar el estado individual del documento.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
