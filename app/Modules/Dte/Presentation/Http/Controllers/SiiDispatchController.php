<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiInputDto;
use App\Modules\Dte\Application\UseCases\Dispatch\PollSiiUploadStatusUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase;
use App\Modules\Dte\Domain\Exceptions\DomainException;
use App\Modules\Dte\Presentation\Http\Requests\PollSiiUploadStatusRequest;
use App\Modules\Dte\Presentation\Http\Requests\SendSignedDteToSiiRequest;
use App\Modules\Dte\Presentation\Http\Resources\SiiDispatchResource;
use Illuminate\Http\JsonResponse;

class SiiDispatchController extends Controller
{
    public function __construct(
        private readonly SendSignedDteToSiiUseCase $sendSignedDteToSiiUseCase,
        private readonly PollSiiUploadStatusUseCase $pollSiiUploadStatusUseCase,
    )
    {}

    public function send(SendSignedDteToSiiRequest $request): JsonResponse
    {
        try {
            $result = $this->sendSignedDteToSiiUseCase->execute(
                new SendSignedDteToSiiInputDto(
                    documentId: (int) $request->validated('document_id')
                )
            );
            $isDeliveryUnknown =
                $result->status
                === DispatchStatus::DELIVERY_UNKNOWN->value;


            return response()->json([

                'message' =>
                    $isDeliveryUnknown

                        ? 'El SII pudo haber recibido el documento, pero la respuesta del upload no pudo confirmarse. No reenvíe el DTE hasta reconciliar su estado.'

                        : 'Envío automático al SII ejecutado.',


                'data' =>
                    new SiiDispatchResource(
                        (object) [

                            'dispatchId' =>
                                $result->dispatchId,

                            'documentId' =>
                                $result->documentId,

                            'batchUuid' =>
                                $result->batchUuid,

                            'status' =>
                                $result->status,

                            'trackId' =>
                                $result->trackId,

                            'uploadStatusCode' =>
                                $result->uploadStatusCode,

                            'uploadStatusMessage' =>
                                $result->uploadStatusMessage,

                            'requestBodyPath' =>
                                $result->requestBodyPath,
                        ]
                    ),

            ], $isDeliveryUnknown ? 202 : 200);

        } catch (DomainException $e) {
            return response()->json(
                [
                    'message' => 'No fue posible enviar el documento al SII.',
                    'error' => $e->getMessage(),
                ],422
            );
        }
    }

    public function poll(PollSiiUploadStatusRequest $request): JsonResponse
    {
        try {
            $result = $this->pollSiiUploadStatusUseCase->execute(
                new PollSiiUploadStatusInputDto(
                    dispatchId: (int) $request->validated('dispatch_id')
                )
            );
            return response()->json([
                'message' => 'Consulta de estado de envío ejecutada',
                'data' => new SiiDispatchResource((object) [
                    'dispatchId' => $result->dispatchId,
                    'status' => $result->status,
                    'trackId' => $result->trackId,
                    'uploadStatusCode' => $result->uploadStatusCode,
                    'uploadStatusMessage' => $result->uploadStatusMessage,
                    'requestBody' => $result->responseBody,
                ])
            ],200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => 'No fue posible consultar el estado del envío.',
                'error' => $e->getMessage(),
            ],422);
        }
    }
}
