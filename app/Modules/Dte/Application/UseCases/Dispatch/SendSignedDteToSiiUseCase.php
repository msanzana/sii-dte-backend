<?php

namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteSiiSendDomainService;
// use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadService;
// use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadService;
use App\Modules\Dte\Infrastructure\Sii\SiiTokenProviderService;
use App\Modules\Dte\Infrastructure\Sii\Exceptions\SiiUploadTransportException;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\EnvioDteEnvelopeBuilderService;
use App\Modules\Dte\Infrastructure\Xml\EnvioDteSignatureService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SendSignedDteToSiiUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteSiiSendDomainService $sendDomainService,
        private readonly EnvioDteEnvelopeBuilderService $envioDteEnvelopeBuilderService,
        private readonly EnvioDteSignatureService $envioDteSignatureService,
        private readonly SiiTokenProviderService $siiTokenProviderService,
        private readonly SiiFacturaUploadService $siiFacturaUploadService,
        private readonly DtePrivateStorageService $storageService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
    ) {}

    public function execute(
        SendSignedDteToSiiInputDto $input
    ): SendSignedDteToSiiResultDto
    {
        /*
        |--------------------------------------------------------------------------
        | FASE 1
        | Preparar y registrar dispatch ANTES de contactar al SII
        |--------------------------------------------------------------------------
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

            $this->sendDomainService
                ->assertCanSend(
                    $document
                );

            /*
            | Evita un segundo POST si existe un envío
            | cuyo resultado todavía no está resuelto.
            */
            $this->assertNoUnresolvedDispatch(
                (int) $document->id()
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

            $certificateContext =
                $this->loadCertificateMaterialForEmisionService
                    ->execute(
                        $document->companyId()
                    );

            $absoluteSignedPath =
                storage_path(
                    $document->signedXmlPath()
                );

            if (
                !File::exists(
                    $absoluteSignedPath
                )
            ) {
                throw new RuntimeException(
                    "No existe el XML firmado del documento en "
                    . $document->signedXmlPath()
                    . "."
                );
            }

            $signedDteXml =
                File::get(
                    $absoluteSignedPath
                );

            if (
                $signedDteXml === false
                || trim($signedDteXml) === ''
            ) {
                throw new RuntimeException(
                    'No fue posible leer el XML firmado del DTE antes de construir el EnvioDTE.'
                );
            }

            $envelopeBuild =
                $this->envioDteEnvelopeBuilderService
                    ->build(
                        document: $document,
                        company: $company,
                        signedDteXml: $signedDteXml
                    );

            $signedEnvelopeXml =
                $this->envioDteSignatureService
                    ->SignSetDte(
                        envioXml:
                            $envelopeBuild[
                                'envelope_xml'
                            ],

                        privateKeyPem:
                            $certificateContext
                                ->privateKeyPem,

                        certificateBase64:
                            $certificateContext
                                ->certificateBase64,

                        modulusBase64:
                            $certificateContext
                                ->modulusBase64,

                        exponentBase64:
                            $certificateContext
                                ->exponentBase64,
                    );

            $envelopeFilename =
                sprintf(
                    'envio_company_%d_td_%d_f_%d_%s.xml',

                    $document->companyId(),

                    $document
                        ->dteType()
                        ->value,

                    $document->folio(),

                    bin2hex(
                        random_bytes(4)
                    )
                );

            $requestBodyPath =
                $this->storageService
                    ->storeString(
                        contents:
                            $signedEnvelopeXml,

                        targetDirectory:
                            'dispatch',

                        targetFileName:
                            $envelopeFilename
                    );

            $environment =
                $document->siiEnvironment()
                ?? config(
                    'dte.default_environment'
                );

            /*
            |--------------------------------------------------------------------------
            | Crear dispatch ANTES del POST
            |--------------------------------------------------------------------------
            */

            $dispatch =
                new SiiDispatch(

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
                        'soap_upload_factura',

                    status:
                        DispatchStatus::PENDING
                            ->value,

                    trackId:
                        null,

                    requestIdentifier:
                        $envelopeBuild[
                            'set_dte_id'
                        ],

                    requestPath:
                        config(
                            "dte.sii.{$environment}.upload.url"
                        ),

                    requestHeaders: [
                        'Cookie' =>
                            'TOKEN=<redacted>',

                        'Content-Type' =>
                            'multipart/form-data',
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

            $this->logRepository->info(

                channel:
                    'sii_dispatch',

                message:
                    'Dispatch preparado antes del upload al SII.',

                context: [

                    'dispatch_id' =>
                        $savedDispatch->id(),

                    'document_id' =>
                        $document->id(),

                    'status' =>
                        $savedDispatch->status(),

                    'request_body_path' =>
                        $savedDispatch
                            ->requestBodyPath(),
                ],

                companyId:
                    $document->companyId(),

                documentId:
                    $document->id(),

                code:
                    'SII_DISPATCH_PREPARED'
            );

            /*
            | La transacción termina aquí.
            | Todavía NO hemos contactado al SII.
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

                'signed_envelope_xml' =>
                    $signedEnvelopeXml,

                'envelope_filename' =>
                    $envelopeFilename,

                'dispatch' =>
                    $savedDispatch,
            ];
        });


        $document =
            $prepared[
                'document'
            ];

        $company =
            $prepared[
                'company'
            ];

        $certificateContext =
            $prepared[
                'certificate_context'
            ];

        $environment =
            $prepared[
                'environment'
            ];

        $signedEnvelopeXml =
            $prepared[
                'signed_envelope_xml'
            ];

        $envelopeFilename =
            $prepared[
                'envelope_filename'
            ];

        $dispatch =
            $prepared[
                'dispatch'
            ];


        /*
        |--------------------------------------------------------------------------
        | FASE 2
        | Obtener TOKEN fuera de la transacción
        |--------------------------------------------------------------------------
        */

        try {

            $tokenContext =
                $this->siiTokenProviderService
                    ->get(
                        environment: $environment,

                        companyId:
                            $document->companyId(),

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

            $token = $tokenContext['token'];
            $this->logRepository->info(

                channel:
                    'sii_authentication',

                message:
                    'TOKEN SII disponible para el envío.',

                context: [

                    'document_id' =>
                        $document->id(),

                    'certificate_id' =>
                        $certificateContext->certificateId,

                    'token_source' =>
                        $tokenContext['source'],
                ],

                companyId:
                    $document->companyId(),

                documentId:
                    $document->id(),

                code:
                    'SII_TOKEN_READY'
            );
        } catch (Throwable $e) {

            /*
            | Aquí todavía NO ejecutamos el POST.
            | Por tanto este error es seguro de marcar
            | como FAILED y eventualmente reintentar.
            */

            $failedDispatch =
                $dispatch->withStatus(

                    status:
                        DispatchStatus::FAILED
                            ->value,

                    errorMessage:
                        'Falló la autenticación previa al upload: '
                        . $e->getMessage()
                );

            $this->dispatchRepository
                ->update(
                    $failedDispatch
                );

            $this->logRepository->error(

                channel:
                    'sii_dispatch',

                message:
                    'Falló la autenticación previa al upload del DTE.',

                context: [

                    'dispatch_id' =>
                        $dispatch->id(),

                    'error' =>
                        $e->getMessage(),
                ],

                companyId:
                    $document->companyId(),

                documentId:
                    $document->id(),

                code:
                    'SII_DISPATCH_AUTH_FAILED'
            );

            throw $e;
        }


        [
            $companyRutBody,
            $companyRutDv
        ] =
            $this->splitRut(
                $company->rut()
            );


        $senderRutBody =
            trim(
                (string)
                config(
                    'dte.sii.sender.rut_body'
                )
            );


        $senderRutDv =
            trim(
                (string)
                config(
                    'dte.sii.sender.rut_dv'
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Estamos a punto de ejecutar el POST
        |--------------------------------------------------------------------------
        */

        $dispatch =
            $this->dispatchRepository
                ->update(

                    $dispatch
                        ->withStatus(
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
                $this->siiFacturaUploadService
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
                            $envelopeFilename,

                        xmlBody:
                            $signedEnvelopeXml,
                    );
            /*
            | Si el SII respondió explícitamente STATUS=5,
            | el TOKEN ya no es utilizable.
            |
            | Lo eliminamos para que el próximo intento
            | obtenga un TOKEN nuevo.
            |
            | IMPORTANTE:
            | no reintentamos automáticamente este mismo upload.
            */
            if (
                ($uploadResult['status_code'] ?? null)
                === '5'
            ) {

                $this->siiTokenProviderService
                    ->forget(
                        environment:
                            $environment,

                        companyId:
                            $document->companyId(),

                        certificateId:
                            $certificateContext
                                ->certificateId,
                    );
            }
        } catch (SiiUploadTransportException $e) {

            [
                $savedDispatch,
                $savedDocument
            ] =
                DB::transaction(
                    function () use (
                        $dispatch,
                        $document,
                        $e
                    ): array {

                        /*
                        |--------------------------------------------------------------------------
                        | No tenemos una respuesta SII concluyente
                        |--------------------------------------------------------------------------
                        |
                        | Puede haber existido tráfico HTTP parcial,
                        | pero no tenemos STATUS/TRACKID suficientes
                        | para afirmar que el envío fue aceptado.
                        |
                        */

                        $unknownDispatch =
                            $dispatch
                                ->withUploadResult(

                                    status:
                                        DispatchStatus::DELIVERY_UNKNOWN
                                            ->value,

                                    trackId:
                                        null,

                                    responseHttpStatus:
                                        $e->httpStatus(),

                                    responseBody:
                                        $e->recoveredBody(),

                                    uploadStatusCode:
                                        null,

                                    uploadStatusMessage:
                                        'Entrega al SII no confirmada por corte de conexión.',

                                    errorMessage:
                                        $e->getMessage()
                                );


                        $savedDispatch =
                            $this->dispatchRepository
                                ->update(
                                    $unknownDispatch
                                );


                        $lockedDocument =
                            $this->documentRepository
                                ->findByIdForUpdate(
                                    $document->id()
                                );


                        if (!$lockedDocument) {

                            throw DocumentNotFoundException
                                ::withId(
                                    $document->id()
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Mantener documento en SENDING
                        |--------------------------------------------------------------------------
                        |
                        | NO permitimos reenviarlo mientras no sepamos
                        | qué ocurrió realmente en el SII.
                        |
                        */

                        $savedDocument =
                            $this->documentRepository
                                ->update(

                                    $lockedDocument
                                        ->withSendingStatus()
                                );


                        return [
                            $savedDispatch,
                            $savedDocument
                        ];
                    }
                );


            $this->logRepository
                ->warning(

                    channel:
                        'sii_dispatch',

                    message:
                        'El upload obtuvo evidencia HTTP parcial pero no una respuesta SII concluyente.',

                    context: [

                        'dispatch_id' =>
                            $savedDispatch->id(),

                        'document_id' =>
                            $savedDocument->id(),

                        'status' =>
                            $savedDispatch->status(),

                        'http_status' =>
                            $e->httpStatus(),

                        'recovered_body_length' =>
                            strlen(
                                (string)
                                $e->recoveredBody()
                            ),

                        'transport' =>
                            $e->diagnostic(),

                        'error' =>
                            $e->getMessage(),
                    ],

                    companyId:
                        $savedDocument
                            ->companyId(),

                    documentId:
                        $savedDocument
                            ->id(),

                    code:
                        'SII_DISPATCH_DELIVERY_UNKNOWN_WITH_DIAGNOSTICS'
                );


            return $this->toResultDto(
                $savedDispatch,
                (int) $savedDocument->id()
            );
        } catch (ConnectionException $e) {

            /*
            |--------------------------------------------------------------------------
            | CASO CRÍTICO
            |--------------------------------------------------------------------------
            |
            | El POST puede haber llegado al SII.
            |
            | Esto fue exactamente lo ocurrido con el folio
            | aceptado recientemente.
            |
            | NO:
            |   - marcar FAILED
            |   - hacer retry automático
            |   - reenviar folio
            |
            */

            [
                $savedDispatch,
                $savedDocument
            ] =
                DB::transaction(
                    function () use (
                        $dispatch,
                        $document,
                        $e
                    ): array {

                        $unknownDispatch =
                            $dispatch
                                ->withUploadResult(

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
                                        'Entrega al SII no confirmada por corte de conexión.',

                                    errorMessage:
                                        $e->getMessage()
                                );


                        $savedDispatch =
                            $this->dispatchRepository
                                ->update(
                                    $unknownDispatch
                                );


                        $lockedDocument =
                            $this->documentRepository
                                ->findByIdForUpdate(
                                    $document->id()
                                );


                        if (!$lockedDocument) {

                            throw DocumentNotFoundException
                                ::withId(
                                    $document->id()
                                );
                        }


                        /*
                        | El documento pasa a SENDING.
                        |
                        | Eso impide volver a ejecutar el upload
                        | mientras averiguamos si llegó al SII.
                        */

                        $savedDocument =
                            $this->documentRepository
                                ->update(

                                    $lockedDocument
                                        ->withSendingStatus()
                                );


                        return [
                            $savedDispatch,
                            $savedDocument
                        ];
                    }
                );


            $this->logRepository
                ->warning(

                    channel:
                        'sii_dispatch',

                    message:
                        'El upload terminó con estado de entrega desconocido; no se reintentará automáticamente.',

                    context: [

                        'dispatch_id' =>
                            $savedDispatch
                                ->id(),

                        'document_id' =>
                            $savedDocument
                                ->id(),

                        'status' =>
                            $savedDispatch
                                ->status(),

                        'error' =>
                            $e->getMessage(),
                    ],

                    companyId:
                        $savedDocument
                            ->companyId(),

                    documentId:
                        $savedDocument
                            ->id(),

                    code:
                        'SII_DISPATCH_DELIVERY_UNKNOWN'
                );


            return $this->toResultDto(
                $savedDispatch,
                (int) $savedDocument->id()
            );

        } catch (SiiUploadException $e) {

            /*
            | Aquí sí tenemos un error conocido
            | del endpoint de upload.
            */

            $failedDispatch =
                $dispatch
                    ->withStatus(

                        status:
                            DispatchStatus::FAILED
                                ->value,

                        errorMessage:
                            $e->getMessage()
                    );


            $this->dispatchRepository
                ->update(
                    $failedDispatch
                );


            $this->logRepository
                ->error(

                    channel:
                        'sii_dispatch',

                    message:
                        'El endpoint de upload respondió con un error conocido.',

                    context: [

                        'dispatch_id' =>
                            $dispatch->id(),

                        'error' =>
                            $e->getMessage(),
                    ],

                    companyId:
                        $document->companyId(),

                    documentId:
                        $document->id(),

                    code:
                        'SII_DISPATCH_UPLOAD_FAILED'
                );


            throw $e;
        }


        /*
        |--------------------------------------------------------------------------
        | FASE 4
        | El SII devolvió una respuesta normal
        |--------------------------------------------------------------------------
        */

        [
            $savedDispatch,
            $savedDocument
        ] =
            DB::transaction(
                function () use (
                    $dispatch,
                    $document,
                    $uploadResult
                ): array {

                    $dispatchStatus =
                        $uploadResult[
                            'status_code'
                        ] === '0'

                            ? DispatchStatus::UPLOAD_OK
                                ->value

                            : DispatchStatus::UPLOAD_REJECTED
                                ->value;


                    $updatedDispatch =
                        $dispatch
                            ->withUploadResult(

                                status:
                                    $dispatchStatus,

                                trackId:
                                    $uploadResult[
                                        'track_id'
                                    ],

                                responseHttpStatus:
                                    $uploadResult[
                                        'http_status'
                                    ],

                                responseBody:
                                    $uploadResult[
                                        'raw_body'
                                    ],

                                uploadStatusCode:
                                    $uploadResult[
                                        'status_code'
                                    ],

                                uploadStatusMessage:
                                    $uploadResult[
                                        'status_message'
                                    ],

                                errorMessage:
                                    $uploadResult[
                                        'status_code'
                                    ] !== '0'

                                        ? $uploadResult[
                                            'status_message'
                                        ]

                                        : null
                            );


                    $savedDispatch =
                        $this->dispatchRepository
                            ->update(
                                $updatedDispatch
                            );


                    $lockedDocument =
                        $this->documentRepository
                            ->findByIdForUpdate(
                                $document->id()
                            );


                    if (!$lockedDocument) {

                        throw DocumentNotFoundException
                            ::withId(
                                $document->id()
                            );
                    }


                    $savedDocument =
                        $lockedDocument;


                    /*
                    | Sólo consideramos SENT cuando
                    | tenemos STATUS=0.
                    */

                    if (
                        $uploadResult[
                            'status_code'
                        ] === '0'
                    ) {

                        $savedDocument =
                            $this->documentRepository
                                ->update(

                                    $lockedDocument
                                        ->withSentStatus()
                                );
                    }


                    return [
                        $savedDispatch,
                        $savedDocument
                    ];
                }
            );


        $this->logRepository->info(

            channel:
                'sii_dispatch',

            message:
                'Upload al SII ejecutado con respuesta disponible.',

            context: [

                'dispatch_id' =>
                    $savedDispatch
                        ->id(),

                'document_id' =>
                    $savedDocument
                        ->id(),

                'track_id' =>
                    $savedDispatch
                        ->trackId(),

                'status' =>
                    $savedDispatch
                        ->status(),

                'upload_status_code' =>
                    $savedDispatch
                        ->uploadStatusCode(),

                'upload_status_message' =>
                    $savedDispatch
                        ->uploadStatusMessage(),
                'transport_recovered' =>
                    $uploadResult[
                        'transport_recovered'
                    ] ?? false,

                'transport' =>
                    $uploadResult[
                        'transport_diagnostics'
                    ] ?? [],
            ],

            companyId:
                $savedDocument
                    ->companyId(),

            documentId:
                $savedDocument
                    ->id(),

            code:
                'SII_DISPATCH_UPLOAD_COMPLETED'
        );


        return $this->toResultDto(
            $savedDispatch,
            (int) $savedDocument->id()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Evitar uploads duplicados
    |--------------------------------------------------------------------------
    */

    private function assertNoUnresolvedDispatch(
        int $documentId
    ): void {

        $latestDispatch =
            $this->dispatchRepository
                ->findLatestByDocumentId(
                    $documentId
                );


        if (!$latestDispatch) {
            return;
        }


        $blockingStatuses = [

            DispatchStatus::PENDING
                ->value,

            DispatchStatus::SENDING
                ->value,

            DispatchStatus::UPLOAD_OK
                ->value,

            DispatchStatus::DELIVERY_UNKNOWN
                ->value,

            DispatchStatus::POLLING
                ->value,

            DispatchStatus::PROCESSED
                ->value,

            DispatchStatus::ACCEPTED
                ->value,

            DispatchStatus::REJECTED
                ->value,

            /*
            | Compatibilidad con valores históricos
            | de tu implementación anterior.
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

            throw InvalidDocumentStateException
                ::because(

                    "El documento {$documentId} ya tiene "
                    . "el dispatch {$latestDispatch->id()} "
                    . "en estado {$latestDispatch->status()}. "
                    . "No se realizará un nuevo upload "
                    . "hasta reconciliarlo."
                );
        }
    }


    private function toResultDto(
        SiiDispatch $dispatch,
        int $documentId
    ): SendSignedDteToSiiResultDto {

        return new SendSignedDteToSiiResultDto(

            dispatchId:
                (int) $dispatch->id(),

            documentId:
                $documentId,

            batchUuid:
                $dispatch->batchUuid(),

            status:
                $dispatch->status(),

            trackId:
                $dispatch->trackId(),

            uploadStatusCode:
                $dispatch
                    ->uploadStatusCode(),

            uploadStatusMessage:
                $dispatch
                    ->uploadStatusMessage(),

            requestBodyPath:
                (string)
                $dispatch
                    ->requestBodyPath(),
        );
    }


    private function splitRut(
        string $rut
    ): array {

        $parts =
            explode(
                '-',
                $rut
            );


        if (
            count($parts) !== 2
        ) {

            throw new RuntimeException(

                "El RUT '{$rut}' "
                . "no tiene el formato cuerpo-dv."
            );
        }


        return [
            trim($parts[0]),
            trim($parts[1])
        ];
    }
}