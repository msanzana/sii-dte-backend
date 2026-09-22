<?php

namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteBoletaSendDomainService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiUploadService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\EnvioBoletaEnvelopeBuilderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendException;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Infrastructure\Xml\EnvioDteSignatureService;

final class SendSignedBoletaToSiiUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteBoletaSendDomainService $boletaSendDomainService,
        private readonly SiiBoletaTokenProviderService $siiBoletaTokenProviderService,
        private readonly EnvioBoletaEnvelopeBuilderService $envioBoletaEnvelopeBuilderService,
        private readonly SiiBoletaApiUploadService $siiBoletaApiUploadService,
        private readonly DtePrivateStorageService $storageService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
        private readonly EnvioDteSignatureService $envioDteSignatureService,
    ) {
    }

    public function execute(
        SendSignedBoletaToSiiInputDto $input
    ): SendSignedBoletaToSiiResultDto
    {
        /*
        |--------------------------------------------------------------------------
        | FASE 1
        | Preparar envío y crear dispatch PENDING
        |--------------------------------------------------------------------------
        |
        | Todo lo que necesita bloqueo de BD ocurre aquí.
        |
        | IMPORTANTE:
        | todavía NO obtenemos TOKEN
        | todavía NO hacemos POST al SII
        |
        */

        $prepared = DB::transaction(function () use ($input): array {

            $document = $this->documentRepository
                ->findByIdForUpdate(
                    $input->documentId
                );

            if (!$document) {
                throw DocumentNotFoundException::withId(
                    $input->documentId
                );
            }

            $this->boletaSendDomainService
                ->assertCanSend(
                    $document
                );

            /*
            |--------------------------------------------------------------------------
            | Evitar un segundo upload del mismo documento
            |--------------------------------------------------------------------------
            */

            $this->assertNoUnresolvedDispatch(
                $document
            );

            $company = $this->companyRepository
                ->findById(
                    $document->companyId()
                );

            if (
                !$company
                || !$company->isActive()
            ) {
                throw CompanyNotFoundException::withId(
                    $document->companyId()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Material del certificado
            |--------------------------------------------------------------------------
            */

            $certificateContext =
                $this->loadCertificateMaterialForEmisionService
                    ->execute(
                        $document->companyId()
                    );

            $environment =
                $document->siiEnvironment()
                ?? config(
                    'dte.default_environment'
                );

            /*
            |--------------------------------------------------------------------------
            | Leer XML firmado de la boleta
            |--------------------------------------------------------------------------
            */

            $absoluteSignedPath = storage_path(
                $document->signedXmlPath()
            );

            if (!File::exists($absoluteSignedPath)) {
                throw new RuntimeException(
                    "No existe el XML firmado de boleta en {$document->signedXmlPath()}."
                );
            }

            $signedXml = File::get(
                $absoluteSignedPath
            );

            if (
                $signedXml === false
                || trim($signedXml) === ''
            ) {
                throw new RuntimeException(
                    'No fue posible leer el XML firmado de Boleta antes del envío.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Construir sobre EnvioBoleta
            |--------------------------------------------------------------------------
            */

            $payloadBuild =
                $this->envioBoletaEnvelopeBuilderService
                    ->build(
                        document: $document,
                        company: $company,
                        signedXml: $signedXml
                    );
            /*
            |--------------------------------------------------------------------------
            | Firmar SetDTE del sobre EnvioBOLETA
            |--------------------------------------------------------------------------
            */

            $signedEnvelopeXml =
                $this->envioDteSignatureService
                    ->SignSetDte(
                        envioXml:
                            $payloadBuild['payload_xml'],

                        privateKeyPem:
                            $certificateContext->privateKeyPem,

                        certificateBase64:
                            $certificateContext->certificateBase64,

                        modulusBase64:
                            $certificateContext->modulusBase64,

                        exponentBase64:
                            $certificateContext->exponentBase64,
                    );
            /*
            |--------------------------------------------------------------------------
            | Nombre del archivo de envío
            |--------------------------------------------------------------------------
            */

            $requestFilename = sprintf(
                'boleta_envio_company_%d_td_%d_f_%d_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(
                    random_bytes(4)
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Guardar XML que será enviado
            |--------------------------------------------------------------------------
            */

            $requestBodyPath =
                $this->storageService
                    ->storeString(
                        contents:
                            $signedEnvelopeXml,

                        targetDirectory:
                            'dispatch_boleta',

                        targetFileName:
                            $requestFilename
                    );

            /*
            |--------------------------------------------------------------------------
            | Crear dispatch ANTES de contactar al SII
            |--------------------------------------------------------------------------
            */

            $dispatch = new SiiDispatch(
                id:
                    null,

                batchUuid:
                    (string) Str::uuid(),

                companyId:
                    $document->companyId(),

                dteDocumentId:
                    $document->id(),

                environment:
                    $environment,

                transportType:
                    'rest_upload_boleta',

                status:
                    DispatchStatus::PENDING
                        ->value,

                trackId:
                    null,

                requestIdentifier:
                    $payloadBuild['request_identifier'],

                requestPath:
                    config(
                        "dte.sii.boleta.{$environment}.send_url"
                    ),

                requestHeaders: [
                    'Content-Type' =>
                        config(
                            "dte.sii.boleta.{$environment}.send_content_type"
                        ),

                    'Cookie' => 'TOKEN=<redacted>',
                ],

                requestBodyPath:
                    $requestBodyPath,

                responseHttpStatus:
                    null,

                responseBody:
                    null,

                uploadStatusCode:
                    null,

                uploadStatusMessage:
                    null,

                retryCount:
                    0,

                nextRetryAt:
                    null,

                errorMessage:
                    null,

                sentAt:
                    null,

                lastPolledAt:
                    null,

                processedAt:
                    null,
            );

            $savedDispatch =
                $this->dispatchRepository
                    ->create(
                        $dispatch
                    );

            /*
            |--------------------------------------------------------------------------
            | Termina la transacción
            |--------------------------------------------------------------------------
            |
            | Desde aquí devolvemos todo lo necesario para continuar.
            |
            | Todavía NO hemos obtenido TOKEN
            | y todavía NO hemos ejecutado el POST.
            |
            */

            return [
                'document' =>
                    $document,

                'company' =>
                    $company,

                'certificate_context' =>
                    $certificateContext,

                'environment' =>
                    $environment,

                'payload_build' =>
                    $payloadBuild,
                
                'signed_envelope_xml' =>
                    $signedEnvelopeXml,

                'request_filename' =>
                    $requestFilename,

                'request_body_path' =>
                    $requestBodyPath,

                'dispatch' =>
                    $savedDispatch,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | FUERA de DB::transaction()
        |--------------------------------------------------------------------------
        */

        $document =
            $prepared['document'];

        $company =
            $prepared['company'];

        $certificateContext =
            $prepared['certificate_context'];

        $environment =
            $prepared['environment'];

        $payloadBuild =
            $prepared['payload_build'];
        
        $signedEnvelopeXml =
            $prepared['signed_envelope_xml'];

        $requestFilename =
            $prepared['request_filename'];

        $requestBodyPath =
            $prepared['request_body_path'];

        $dispatch =
            $prepared['dispatch'];

        /*
        |--------------------------------------------------------------------------
        | FASE 2
        | Obtener TOKEN fuera de la transacción
        |--------------------------------------------------------------------------
        */

        try {

            $tokenContext =
                $this->siiBoletaTokenProviderService
                    ->get(
                        environment:
                            $environment,

                        companyId:
                            $certificateContext->companyId,

                        certificateId:
                            $certificateContext->certificateId,

                        privateKeyPem:
                            $certificateContext->privateKeyPem,

                        certificateBase64:
                            $certificateContext->certificateBase64,

                        modulusBase64:
                            $certificateContext->modulusBase64,

                        exponentBase64:
                            $certificateContext->exponentBase64,
                    );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Falló la autenticación antes del POST
            |--------------------------------------------------------------------------
            |
            | Aquí sabemos que todavía NO comenzó el upload al SII.
            |
            | Por eso este caso es distinto de DELIVERY_UNKNOWN:
            |
            | TOKEN falla       -> FAILED
            | POST se interrumpe -> DELIVERY_UNKNOWN
            |
            */

            $dispatch =
                $this->dispatchRepository
                    ->update(
                        $dispatch->withStatus(
                            status:
                                DispatchStatus::FAILED
                                    ->value,

                            errorMessage:
                                $e->getMessage()
                        )
                    );

            throw $e;
        }

        $token =
            $tokenContext['token'];

        /*
        |--------------------------------------------------------------------------
        | Preparar RUT empresa
        |--------------------------------------------------------------------------
        */

        [
            $companyRutBody,
            $companyRutDv
        ] = $this->splitRut(
            $company->rut()
        );

        /*
        |--------------------------------------------------------------------------
        | Preparar RUT sender
        |--------------------------------------------------------------------------
        */

        $senderRutBody = trim(
            (string) config(
                'dte.sii.sender.rut_body'
            )
        );

        $senderRutDv = trim(
            (string) config(
                'dte.sii.sender.rut_dv'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | FASE 3
        | POST real al SII
        |--------------------------------------------------------------------------
        |
        | También ocurre FUERA de la transacción.
        |
        | En el siguiente ciclo TDD agregaremos primero
        | la transición PENDING -> SENDING.
        |
        */
        /*
        |--------------------------------------------------------------------------
        | Marcar dispatch como SENDING antes del POST
        |--------------------------------------------------------------------------
        |
        | Desde este momento estamos a punto de contactar al SII.
        | Si después ocurre un corte de conexión, ya tendremos registrado
        | que el intento de envío efectivamente comenzó.
        |
        */

        $dispatch =
            $this->dispatchRepository
                ->update(
                    $dispatch->withStatus(
                        status:
                            DispatchStatus::SENDING
                                ->value
                    )
                );
        /*
        |--------------------------------------------------------------------------
        | FASE 3
        | POST real al SII
        |--------------------------------------------------------------------------
        */
        try {

            $uploadResult =
                $this->siiBoletaApiUploadService
                    ->upload(
                        environment:
                            $environment,

                        token:
                            $token,

                        senderRutBody:
                            $senderRutBody,

                        senderRutDv:
                            $senderRutDv,

                        companyRutBody:
                            $companyRutBody,

                        companyRutDv:
                            $companyRutDv,

                        filename:
                            $requestFilename,

                        xmlPayload:
                            $signedEnvelopeXml
                    );

        } catch (ConnectionException $e) {

            /*
            |--------------------------------------------------------------------------
            | Resultado de entrega desconocido
            |--------------------------------------------------------------------------
            |
            | Una ConnectionException NO significa necesariamente que el SII
            | no haya recibido la boleta.
            |
            | El servidor pudo haber recibido el POST y la conexión haberse
            | interrumpido antes de que nosotros recibiéramos la respuesta.
            |
            | Por eso:
            |
            | - NO marcamos FAILED.
            | - NO reenviamos automáticamente.
            | - dejamos el dispatch en DELIVERY_UNKNOWN.
            | - dejamos el documento en SENDING.
            |
            */

            $reconciled =
                DB::transaction(
                    function () use (
                        $dispatch,
                        $document,
                        $e
                    ): array {

                        /*
                        |--------------------------------------------------------------------------
                        | Marcar dispatch como DELIVERY_UNKNOWN
                        |--------------------------------------------------------------------------
                        */

                        $deliveryUnknownDispatch =
                            $dispatch->withUploadResult(
                                status:
                                    DispatchStatus::DELIVERY_UNKNOWN
                                        ->value,

                                trackId:
                                    null,

                                responseHttpStatus:
                                    null,

                                responseBody:
                                    null,

                                uploadStatusCode:
                                    null,

                                uploadStatusMessage:
                                    null,

                                errorMessage:
                                    $e->getMessage()
                            );

                        $savedDispatch =
                            $this->dispatchRepository
                                ->update(
                                    $deliveryUnknownDispatch
                                );

                        /*
                        |--------------------------------------------------------------------------
                        | Volver a bloquear el documento
                        |--------------------------------------------------------------------------
                        */

                        $lockedDocument =
                            $this->documentRepository
                                ->findByIdForUpdate(
                                    (int) $document->id()
                                );

                        if (!$lockedDocument) {
                            throw DocumentNotFoundException::withId(
                                (int) $document->id()
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Mantener documento en SENDING
                        |--------------------------------------------------------------------------
                        */

                        $updatedDocument =
                            $lockedDocument
                                ->withSendingStatus();

                        $this->documentRepository
                            ->update(
                                $updatedDocument
                            );

                        return [
                            'dispatch' =>
                                $savedDispatch,

                            'document' =>
                                $updatedDocument,
                        ];
                    }
                );

            $savedDispatch =
                $reconciled['dispatch'];

            $document =
                $reconciled['document'];

            /*
            |--------------------------------------------------------------------------
            | Terminar el caso de uso aquí
            |--------------------------------------------------------------------------
            |
            | Es fundamental NO continuar con mapDispatchStatusOnSend(),
            | porque no conocemos el resultado real del POST.
            |
            */

            return new SendSignedBoletaToSiiResultDto(
                dispatchId:
                    $savedDispatch->id(),

                documentId:
                    (int) $document->id(),

                batchUuid:
                    $savedDispatch->batchUuid(),

                status:
                    $savedDispatch->status(),

                trackId:
                    $savedDispatch->trackId(),

                sendStatusCode:
                    $savedDispatch->uploadStatusCode(),

                sendStatusMessage:
                    $savedDispatch->uploadStatusMessage(),

                requestBodyPath:
                    (string) $savedDispatch->requestBodyPath(),
            );
        }
        catch (SiiBoletaSendException $e) {

            /*
            |--------------------------------------------------------------------------
            | Error conocido devuelto por el endpoint
            |--------------------------------------------------------------------------
            |
            | A diferencia de ConnectionException, aquí sí recibimos una respuesta
            | HTTP y el servicio pudo determinar que el envío falló.
            |
            | Por eso:
            |
            | - NO usamos DELIVERY_UNKNOWN.
            | - marcamos el dispatch FAILED.
            | - conservamos el mensaje de error.
            | - propagamos la excepción original.
            |
            */

            $dispatch =
                $this->dispatchRepository
                    ->update(
                        $dispatch->withUploadResult(
                            status:
                                DispatchStatus::FAILED
                                    ->value,

                            trackId:
                                null,

                            responseHttpStatus:
                                $e->httpStatus(),

                            responseBody:
                                $e->rawBody(),

                            uploadStatusCode:
                                null,

                            uploadStatusMessage:
                                null,

                            errorMessage:
                                $e->getMessage()
                        )
                    );

            throw $e;
        }

        /*
        |--------------------------------------------------------------------------
        | Resultado recibido desde el endpoint de upload
        |--------------------------------------------------------------------------
        */

        $dispatchStatus =
            $this->mapDispatchStatusOnSend(
                $uploadResult['track_id'],
                $uploadResult['status_code']
            );

        /*
        |--------------------------------------------------------------------------
        | Actualizar EL MISMO dispatch creado antes del POST
        |--------------------------------------------------------------------------
        */

        $updatedDispatch =
            $dispatch->withUploadResult(
                status:
                    $dispatchStatus,

                trackId:
                    $uploadResult['track_id'],

                responseHttpStatus:
                    $uploadResult['http_status'],

                responseBody:
                    $uploadResult['raw_body'],

                uploadStatusCode:
                    $uploadResult['status_code'],

                uploadStatusMessage:
                    $uploadResult['status_message'],

                errorMessage:
                    $dispatchStatus === 'upload_rejected'
                        ? $uploadResult['status_message']
                        : null
            );

        $savedDispatch =
            $this->dispatchRepository
                ->update(
                    $updatedDispatch
                );

        /*
        |--------------------------------------------------------------------------
        | Si el envío fue reconocido como enviado,
        | actualizar el documento
        |--------------------------------------------------------------------------
        */

        if ($dispatchStatus === 'sent') {

            $document =
                $document->withSentStatus();

            $this->documentRepository
                ->update(
                    $document
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Log integración
        |--------------------------------------------------------------------------
        */

        $this->logRepository->info(
            channel:
                'sii_boleta_dispatch',

            message:
                'Envio REST de boleta ejecutado.',

            context: [
                'dispatch_id' =>
                    $savedDispatch->id(),

                'document_id' =>
                    $document->id(),

                'track_id' =>
                    $savedDispatch->trackId(),

                'send_status_code' =>
                    $savedDispatch->uploadStatusCode(),

                'send_status_message' =>
                    $savedDispatch->uploadStatusMessage(),
            ],

            companyId:
                $document->companyId(),

            documentId:
                $document->id(),

            code:
                'SII_BOLETA_DISPATCH_CREATED'
        );

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return new SendSignedBoletaToSiiResultDto(
            dispatchId:
                $savedDispatch->id(),

            documentId:
                (int) $document->id(),

            batchUuid:
                $savedDispatch->batchUuid(),

            status:
                $savedDispatch->status(),

            trackId:
                $savedDispatch->trackId(),

            sendStatusCode:
                $savedDispatch->uploadStatusCode(),

            sendStatusMessage:
                $savedDispatch->uploadStatusMessage(),

            requestBodyPath:
                (string) $savedDispatch->requestBodyPath(),
        );
    }

    private function mapDispatchStatusOnSend(
        ?string $trackId,
        ?string $statusCode
    ): string
    {
        if (
            $trackId !== null
            && trim($trackId) !== ''
        ) {
            return 'sent';
        }

        $normalized =
            mb_strtolower(
                trim(
                    (string) $statusCode
                )
            );

        if (
            in_array(
                $normalized,
                [
                    '0',
                    'ok',
                    'accepted',
                    'aceptado',
                    'rec',
                ],
                true
            )
        ) {
            return 'sent';
        }

        return 'upload_rejected';
    }

    /*
    |--------------------------------------------------------------------------
    | Evitar uploads duplicados
    |--------------------------------------------------------------------------
    */
    private function assertNoUnresolvedDispatch(
        DteDocument $document
    ): void
    {
        $documentId = (int) $document->id();

        $latestDispatch =
            $this->dispatchRepository
                ->findLatestByDocumentId(
                    $documentId
                );

        if (!$latestDispatch) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Reproceso controlado de un rechazo RSC
        |--------------------------------------------------------------------------
        |
        | Un dispatch REJECTED sigue siendo bloqueante por defecto.
        |
        | Solamente permitimos un nuevo upload cuando podemos demostrar que:
        |
        | - el documento fue reconstruido completamente hasta SIGNED;
        | - conserva RSC como causa del reproceso;
        | - el último dispatch realmente terminó REJECTED;
        | - dicho dispatch también fue rechazado por RSC.
        |
        | Esta excepción existe para el flujo explícito:
        |
        | SENT
        |   -> NEEDS_RESEND
        |   -> XML_BUILT
        |   -> TED_BUILT
        |   -> SIGNED
        |   -> nuevo dispatch
        |
        */
        $isControlledRscReprocess =
            $document->status() === DteStatus::SIGNED->value
            && $document->lastErrorCode() === 'RSC'
            && $latestDispatch->status() === DispatchStatus::REJECTED->value
            && $latestDispatch->uploadStatusCode() === 'RSC';

        if ($isControlledRscReprocess) {
            return;
        }

        $blockingStatuses = [
            DispatchStatus::PENDING->value,
            DispatchStatus::SENDING->value,
            DispatchStatus::UPLOAD_OK->value,
            DispatchStatus::DELIVERY_UNKNOWN->value,
            DispatchStatus::POLLING->value,
            DispatchStatus::PROCESSED->value,
            DispatchStatus::ACCEPTED->value,
            DispatchStatus::REJECTED->value,

            /*
            | Compatibilidad con valores históricos
            */
            'send',
            'sent',
        ];

        if (
            in_array(
                $latestDispatch->status(),
                $blockingStatuses,
                true
            )
        ) {
            throw InvalidDocumentStateException::because(
                "El documento {$documentId} ya tiene "
                . "el dispatch {$latestDispatch->id()} "
                . "en estado {$latestDispatch->status()}. "
                . "No se realizará un nuevo upload "
                . "hasta reconciliarlo."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Separar RUT cuerpo / DV
    |--------------------------------------------------------------------------
    */

    private function splitRut(
        string $rut
    ): array
    {
        $parts = explode(
            '-',
            $rut
        );

        if (
            count($parts) !== 2
        ) {
            throw new RuntimeException(
                "El RUT '{$rut}' no tiene el formato cuerpo-dv."
            );
        }

        return [
            trim($parts[0]),
            trim($parts[1]),
        ];
    }

}
