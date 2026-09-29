<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use LogicException;
use Throwable;
use App\Modules\Dte\Application\Services\DocumentAutomationFailureRetryabilityPolicyService;


final class HandleDocumentAutomationFailureService
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly ScheduleDocumentAutomationRetryService $scheduleRetryService,
        private readonly ?DocumentAutomationFailureRetryabilityPolicyService $retryabilityPolicyService = null,
    ) {
    }

    public function execute(
        int $documentId,
        string $action,
        Throwable $failure
    ): DteDocument {
        return DB::transaction(
            function () use (
                $documentId,
                $action,
                $failure
            ): DteDocument {

                /*
                |--------------------------------------------------------------------------
                | Recargar y bloquear después del rollback anterior
                |--------------------------------------------------------------------------
                |
                | Este servicio debe ejecutarse DESPUÉS de que haya fallado
                | build_xml, build_ted o sign_xml.
                |
                | Es decir, la transacción interna de aquella etapa ya terminó
                | con rollback antes de que lleguemos aquí.
                |
                | Ahora abrimos una transacción nueva exclusivamente para
                | persistir el estado del retry.
                |
                */

                $document =
                    $this->documentRepository
                        ->findByIdForUpdate(
                            $documentId
                        );

                if (!$document) {
                    throw DocumentNotFoundException::withId(
                        $documentId
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Códigos de diagnóstico por etapa
                |--------------------------------------------------------------------------
                */

                [
                    $failureCode,
                    $exhaustedCode,
                    $notRetryableCode,
                ] =
                    $this->resolveErrorCodes(
                        $action
                    );

                /*
                |--------------------------------------------------------------------------
                | Todavía podemos programar otro intento
                |--------------------------------------------------------------------------
                */
                $retryabilityPolicy =
                    $this->retryabilityPolicyService
                    ?? new DocumentAutomationFailureRetryabilityPolicyService();

                if (
                    !$retryabilityPolicy->shouldRetry(
                        $failure
                    )
                ) {
                    $updated =
                        $document->withAutomationRetryNotRetryable(
                            action:
                                $action,

                            errorCode:
                                $notRetryableCode,

                            errorMessage:
                                $failure->getMessage()
                        );

                    return $this->documentRepository
                        ->update(
                            $updated
                        );
                }
                if (
                    $this->scheduleRetryService
                        ->canScheduleRetry(
                            document:
                                $document,
                            action:
                                $action
                        )
                ) {
                    $updated =
                        $this->scheduleRetryService
                            ->execute(
                                document:
                                    $document,

                                action:
                                    $action,

                                errorCode:
                                    $failureCode,

                                errorMessage:
                                    $failure->getMessage()
                            );

                    return $this->documentRepository
                        ->update(
                            $updated
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Retry agotado
                |--------------------------------------------------------------------------
                |
                | Si el documento llegó aquí con retry_count == máximo,
                | NO programamos una fecha nueva.
                |
                | Conservamos:
                |
                | - action
                | - retry_count
                | - folio
                | - caf_id
                | - folio_reservation_id
                | - external_system_id
                | - artefactos válidos de etapas anteriores
                |
                | Y dejamos automation_next_retry_at = null.
                |
                | El pump ya está probado para excluir este estado.
                |
                */

                $updated =
                    $document
                        ->withAutomationRetryExhausted(
                            action:
                                $action,

                            errorCode:
                                $exhaustedCode,

                            errorMessage:
                                sprintf(
                                    'Se agotaron los retries automáticos de %s. Último error: %s',
                                    $action,
                                    $failure->getMessage()
                                )
                        );

                return $this->documentRepository
                    ->update(
                        $updated
                    );
            }
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    /** @return array{0: string, 1: string, 2: string} */
    private function resolveErrorCodes(
        string $action
    ): array {
        return match ($action) {
            'build_xml' => [
                'AUTOMATION_BUILD_XML_FAILED',
                'AUTOMATION_BUILD_XML_RETRY_EXHAUSTED',
                'AUTOMATION_BUILD_XML_NOT_RETRYABLE',
            ],

            'build_ted' => [
                'AUTOMATION_BUILD_TED_FAILED',
                'AUTOMATION_BUILD_TED_RETRY_EXHAUSTED',
                'AUTOMATION_BUILD_TED_NOT_RETRYABLE',
            ],

            'sign_xml' => [
                'AUTOMATION_SIGN_XML_FAILED',
                'AUTOMATION_SIGN_XML_RETRY_EXHAUSTED',
                'AUTOMATION_SIGN_XML_NOT_RETRYABLE',
            ],

            default => throw new LogicException(
                "La acción {$action} no admite retry interno de automatización."
            ),
        };
    }
}