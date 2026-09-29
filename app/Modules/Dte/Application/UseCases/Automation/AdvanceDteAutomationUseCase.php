<?php

namespace App\Modules\Dte\Application\UseCases\Automation;

use App\Modules\Dte\Application\DTOs\AdvancedDteAutomationResultDto;
use App\Modules\Dte\Application\DTOs\AdvanceDteAutomationInputDto;
use App\Modules\Dte\Application\DTOs\BuildDteXmlInputDto;
use App\Modules\Dte\Application\DTOs\BuildTedInputDto;
use App\Modules\Dte\Application\DTOs\PrepareDteDocumentForXmlInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SignDteXmlInputDto;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryScheduledException;
use App\Modules\Dte\Application\Services\HandleDocumentAutomationFailureService;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedDteToSiiUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildDteXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\BuildTedUseCase;
use App\Modules\Dte\Application\UseCases\Document\PrepareDteDocumentForXmlUseCase;
use App\Modules\Dte\Application\UseCases\Document\SignDteXmlUseCase;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteAutomationPlannerService;
use Throwable;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryExhaustedException;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationNotRetryableException;

final class AdvanceDteAutomationUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly DteAutomationPlannerService $automationPlannerService,
        private readonly PrepareDteDocumentForXmlUseCase $prepareDteDocumentForXmlUseCase,
        private readonly BuildDteXmlUseCase $buildDteXmlUseCase,
        private readonly BuildTedUseCase $buildTedUseCase,
        private readonly SignDteXmlUseCase $signDteXmlUseCase,
        private readonly SendSignedDteToSiiUseCase $sendSignedDteToSiiUseCase,
        private readonly SendSignedBoletaToSiiUseCase $sendSignedBoletaToSiiUseCase,
        private readonly HandleDocumentAutomationFailureService $handleDocumentAutomationFailureService,
    ) {
    }

    public function execute(
        AdvanceDteAutomationInputDto $input
    ): AdvancedDteAutomationResultDto {
        /*
        |--------------------------------------------------------------------------
        | Documento actual
        |--------------------------------------------------------------------------
        */

        $document =
            $this->documentRepository
                ->findById(
                    $input->documentId
                );

        if (!$document) {
            throw DocumentNotFoundException::withId(
                $input->documentId
            );
        }

        $previousStatus =
            $document->status();

        $action =
            $this->automationPlannerService
                ->resolveNextAction(
                    $document
                );

        /*
        |--------------------------------------------------------------------------
        | Nada pendiente
        |--------------------------------------------------------------------------
        */

        if ($action === null) {
            return new AdvancedDteAutomationResultDto(
                documentId:
                    $input->documentId,

                previousStatus:
                    $previousStatus,

                currentStatus:
                    $previousStatus,

                executeAction:
                    null,

                shouldRequeueImmediately:
                    false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ejecutar siguiente etapa
        |--------------------------------------------------------------------------
        |
        | Cada use case interno mantiene su propia transacción.
        |
        | Si build_xml, build_ted o sign_xml fallan, su DB::transaction()
        | habrá hecho rollback antes de que la excepción llegue a este catch.
        |
        | Sólo entonces persistimos el retry en una NUEVA transacción mediante
        | HandleDocumentAutomationFailureService.
        |
        */

        try {
            match ($action) {
                'prepare_for_xml' =>
                    $this->prepareDteDocumentForXmlUseCase
                        ->execute(
                            new PrepareDteDocumentForXmlInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                'build_xml' =>
                    $this->buildDteXmlUseCase
                        ->execute(
                            new BuildDteXmlInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                'build_ted' =>
                    $this->buildTedUseCase
                        ->execute(
                            new BuildTedInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                'sign_xml' =>
                    $this->signDteXmlUseCase
                        ->execute(
                            new SignDteXmlInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                'send_factura' =>
                    $this->sendSignedDteToSiiUseCase
                        ->execute(
                            new SendSignedDteToSiiInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                'send_boleta' =>
                    $this->sendSignedBoletaToSiiUseCase
                        ->execute(
                            new SendSignedBoletaToSiiInputDto(
                                documentId:
                                    $input->documentId
                            )
                        ),

                default =>
                    throw new \LogicException(
                        "Acción de automatización DTE no soportada: {$action}"
                    ),
            };

        } catch (Throwable $failure) {

            /*
            |--------------------------------------------------------------------------
            | Solamente las etapas internas admiten este retry funcional
            |--------------------------------------------------------------------------
            |
            | prepare_for_xml:
            |     mantiene el comportamiento técnico normal.
            |
            | send_factura / send_boleta:
            |     utilizan su propio mecanismo de dispatch_retry.
            |
            | Cualquier error de esas etapas debe continuar propagándose.
            |
            */

            if (
                !in_array(
                    $action,
                    [
                        'build_xml',
                        'build_ted',
                        'sign_xml',
                    ],
                    true
                )
            ) {
                throw $failure;
            }

            /*
            |--------------------------------------------------------------------------
            | Persistir retry DESPUÉS del rollback de la etapa
            |--------------------------------------------------------------------------
            |
            | Este servicio abre una nueva transacción:
            |
            | - findByIdForUpdate()
            | - programa otro retry si quedan intentos
            | - o lo marca agotado si llegó al máximo
            | - update()
            |
            */

            $handledDocument =
                $this->handleDocumentAutomationFailureService
                    ->execute(
                        documentId:
                            $input->documentId,

                        action:
                            $action,

                        failure:
                            $failure
                    );

            /*
            |--------------------------------------------------------------------------
            | Fallo no retryable
            |--------------------------------------------------------------------------
            |
            | El handler ya persistió el documento como terminal sin consumir
            | intentos automáticos.
            |
            | Este caso debe distinguirse de RETRY_EXHAUSTED porque:
            |
            | - retry_count puede seguir en 0;
            | - nunca hubo intención de reintentar;
            | - repetir automáticamente no resolvería el problema.
            |
            */

            if (
                str_ends_with(
                    (string) $handledDocument->lastErrorCode(),
                    '_NOT_RETRYABLE'
                )
            ) {
                throw new DocumentAutomationNotRetryableException(
                    documentId:
                        $input->documentId,

                    action:
                        $action,

                    previous:
                        $failure
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Retry agotado
            |--------------------------------------------------------------------------
            |
            | Si no es NOT_RETRYABLE y ya no existe next_retry_at, significa que
            | los intentos funcionales permitidos se agotaron.
            |
            */

            if (
                $handledDocument->automationNextRetryAt()
                === null
            ) {
                throw new DocumentAutomationRetryExhaustedException(
                    documentId:
                        $input->documentId,

                    action:
                        $action,

                    previous:
                        $failure
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Retry programado
            |--------------------------------------------------------------------------
            |
            | Existe next_retry_at, por lo que el pump podrá retomar el documento
            | cuando venza el backoff.
            |
            */

            throw new DocumentAutomationRetryScheduledException(
                documentId:
                    $input->documentId,

                action:
                    $action,

                previous:
                    $failure
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Recargar documento después de una transición exitosa
        |--------------------------------------------------------------------------
        */

        $updatedDocument =
            $this->documentRepository
                ->findById(
                    $input->documentId
                );

        if (!$updatedDocument) {
            throw DocumentNotFoundException::withId(
                $input->documentId
            );
        }

        return new AdvancedDteAutomationResultDto(
            documentId:
                $updatedDocument->id(),

            previousStatus:
                $previousStatus,

            currentStatus:
                $updatedDocument->status(),

            executeAction:
                $action,

            shouldRequeueImmediately:
                $this->automationPlannerService
                    ->shouldRequeueImmediately(
                        action:
                            $action,

                        currentStatus:
                            $updatedDocument->status()
                    ),
        );
    }
}