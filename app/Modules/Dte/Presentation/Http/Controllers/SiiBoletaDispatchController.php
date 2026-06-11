<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiInputDto;
use App\Modules\Dte\Application\UseCases\Dispatch\PollBoletaDispatchStatusUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use App\Modules\Dte\Presentation\Http\Requests\PollBoletaDispatchStatusRequest;
use App\Modules\Dte\Presentation\Http\Requests\SendSignedBoletaToSiiRequest;
use App\Modules\Dte\Presentation\Http\Resources\BoletaDispatchResource;
use DomainException;
use Illuminate\Http\JsonResponse;

class SiiBoletaDispatchController extends Controller
{
    public function __construct(
        private readonly SendSignedBoletaToSiiUseCase $sendSignedBoletaToSiiUseCase,
        private readonly PollBoletaDispatchStatusUseCase $pollBoletaDispatchStatusUseCase,
    )
    {}

    public function send(SendSignedBoletaToSiiRequest $request): JsonResponse
    {
        try {
            $result = $this->sendSignedBoletaToSiiUseCase->execute(
                new SendSignedBoletaToSiiInputDto(
                    documentId: (int) $request->validated('document_id')
                )
            );

            return response()->json([
                'message' => 'Envio REST de boleta ejecutado.',
                'data' => new BoletaDispatchResource((object) [
                    'dispatchId' => $result->dispatchId,
                    'documentId' => $result->documentId,
                    'batchUuid' => $result->batchUuid,
                    'status' => $result->status,
                    'trackId' => $result->trackId,
                    'sendStatusCode' => $result->sendStatusCode,
                    'sendStatusMessage' => $result->sendStatusMessage,
                    'requestBodyPath' => $result->requestBodyPath,
                ]),
            ],200);

        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible enviar la boleta al SII.',
                'error' => $e->getMessage(),
            ],422);
        }
    }

    public function poll(PollBoletaDispatchStatusRequest $request):JsonResponse
    {
        try {
            $result = $this->pollBoletaDispatchStatusUseCase->execute(
                new PollBoletaDispatchStatusInputDto(
                    dispatchId: (int) $request->validated('dispatch_id')
                )
            );

            return response()->json([
                'message' => 'Consulta REST de estado de envío de boleta ejecutada',
                'data' => new BoletaDispatchResource((object) [
                    'dispatchId' => $result->dispatchId,
                    'status' => $result->status,
                    'trackId' => $result->trackId,
                    'sendStatusCode' => $result->sendStatusCode,
                    'sendStatusMessage' => $result->sendStatusMessage,
                    'rawBody' => $result->rawBody,
                ]),
            ],200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible consultar el estado del envío de boleta.',
                'error' => $e->getMessage(),
            ],422);
        }
    }
}
