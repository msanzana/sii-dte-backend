<?php

namespace App\Jobs\Dte\Automation;

use App\Modules\Dte\Application\DTOs\AdvanceDteAutomationInputDto;
use App\Modules\Dte\Application\UseCases\Automation\AdvanceDteAutomationUseCase;
use App\Modules\Dte\Domain\Exceptions\DispatchRetryScheduledException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryScheduledException;
use Throwable;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationRetryExhaustedException;
use App\Modules\Dte\Application\Exceptions\DocumentAutomationNotRetryableException;

class AdvanceDteDocumentPipelineJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $documentId,
    ) {
        $this->onConnection((string) config('dte.automation.queue_connection'));
        $this->onQueue((string) config('dte.automation.queues.pipeline'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return 'dte-document-pipeline:' . $this->documentId;
    }

    public function tries(): int
    {
        return (int) config('dte.automation.tries.pipeline', 5);
    }

    public function backoff(): array
    {
        return (array) config('dte.automation.backoff.pipeline', [5, 15, 60]);
    }

    public function middleware(): array
    {
        return [
            Skip::when(
                fn (): bool => !filter_var(
                    config('dte.automation.enabled', true),
                    FILTER_VALIDATE_BOOLEAN
                )
            ),

            (new WithoutOverlapping(
                'dte-document-pipeline:' . $this->documentId
            ))
                ->dontRelease()
                ->expireAfter(120),
        ];
    }

    public function handle(
        AdvanceDteAutomationUseCase $advanceDteAutomationUseCase
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Contexto del job
        |--------------------------------------------------------------------------
        */

        Context::add(
            'job',
            'AdvanceDteDocumentPipelineJob'
        );

        Context::add(
            'document_id',
            $this->documentId
        );

        /*
        |--------------------------------------------------------------------------
        | Avanzar pipeline
        |--------------------------------------------------------------------------
        |
        | Normalmente cualquier excepción debe seguir propagándose hacia
        | Laravel para que funcionen los tries/backoff técnicos del job.
        |
        | Existe UNA sola excepción:
        |
        | DispatchRetryScheduledException
        |
        | Esa excepción significa que el use case ya persistió correctamente
        | un retry de negocio mediante:
        |
        | - FAILED
        | - retry_count
        | - next_retry_at
        |
        | Por lo tanto ese caso NO debe consumir un retry técnico de Laravel.
        |
        */

        try {
            $result =
                $advanceDteAutomationUseCase
                    ->execute(
                        new AdvanceDteAutomationInputDto(
                            documentId:
                                $this->documentId
                        )
                    );

        } catch (DispatchRetryScheduledException $e) {

            /*
            |--------------------------------------------------------------------------
            | Avanzar pipeline
            |--------------------------------------------------------------------------
            |
            | Normalmente cualquier excepción debe seguir propagándose hacia
            | Laravel para que funcionen los tries/backoff técnicos del job.
            |
            | Existen dos excepciones controladas:
            |
            | - DispatchRetryScheduledException
            | - DocumentAutomationRetryScheduledException
            |
            | Ambas indican que un retry funcional ya fue persistido
            | correctamente, por lo que NO deben consumir un retry técnico
            | de Laravel.
            |
            */

            logger()->info(
                'Retry de negocio DTE programado; el job termina sin consumir retry técnico.',
                [
                    'document_id' =>
                        $this->documentId,

                    'dispatch_id' =>
                        $e->dispatchId(),

                    'retry_document_id' =>
                        $e->documentId(),

                    'reason' =>
                        $e->getPrevious()?->getMessage()
                        ?? $e->getMessage(),
                ]
            );

            return;
        } catch (DocumentAutomationRetryScheduledException $e) {

            /*
            |--------------------------------------------------------------------------
            | Retry funcional interno ya programado
            |--------------------------------------------------------------------------
            |
            | El documento ya quedó persistido con:
            |
            | - automation_retry_action
            | - automation_retry_count
            | - automation_next_retry_at
            |
            | Por lo tanto no relanzamos la excepción. El pump volverá a
            | seleccionar el documento cuando venza automation_next_retry_at.
            |
            */

            logger()->info(
                'Retry interno de automatización DTE programado; el job termina sin consumir retry técnico.',
                [
                    'document_id' =>
                        $e->documentId(),

                    'action' =>
                        $e->action(),

                    'reason' =>
                        $e->getPrevious()?->getMessage()
                        ?? $e->getMessage(),
                ]
            );

            return;
        }
        catch (DocumentAutomationRetryExhaustedException $e) {

            /*
            |--------------------------------------------------------------------------
            | Retry funcional interno agotado
            |--------------------------------------------------------------------------
            |
            | El documento ya quedó persistido en estado terminal:
            |
            | - automation_retry_action conserva la etapa fallida
            | - automation_retry_count conserva el máximo alcanzado
            | - automation_next_retry_at = null
            |
            | No debemos relanzar esta excepción porque no corresponde consumir
            | retries técnicos de Laravel por un retry funcional ya agotado.
            |
            | El documento queda detenido hasta una futura intervención o
            | mecanismo explícito de reproceso.
            |
            */

            logger()->warning(
                'Retries internos de automatización DTE agotados; el documento queda detenido sin consumir retry técnico.',
                [
                    'document_id' =>
                        $e->documentId(),

                    'action' =>
                        $e->action(),

                    'reason' =>
                        $e->getPrevious()?->getMessage()
                        ?? $e->getMessage(),
                ]
            );

            return;
        }
            catch (DocumentAutomationNotRetryableException $e) {

            /*
            |--------------------------------------------------------------------------
            | Fallo funcional interno no retryable
            |--------------------------------------------------------------------------
            |
            | El documento ya quedó persistido en un estado terminal porque el
            | error es determinístico y repetir automáticamente la misma etapa
            | no lo resolvería.
            |
            | Por ejemplo:
            |
            | - DTE inválido;
            | - estado de dominio incompatible;
            | - datos TED inválidos;
            | - referencias de negocio inexistentes.
            |
            | No debemos relanzar esta excepción porque el fallo funcional ya fue
            | clasificado y persistido. Tampoco corresponde consumir tries/backoff
            | técnicos de Laravel.
            |
            */

            logger()->warning(
                'Fallo de automatización DTE no admite retry automático; el documento queda detenido sin consumir retry técnico.',
                [
                    'document_id' =>
                        $e->documentId(),

                    'action' =>
                        $e->action(),

                    'reason' =>
                        $e->getPrevious()?->getMessage()
                        ?? $e->getMessage(),
                ]
            );

            return;
        }
        /*
        |--------------------------------------------------------------------------
        | Requeue inmediata del pipeline normal
        |--------------------------------------------------------------------------
        |
        | Esto sigue siendo el comportamiento existente para etapas internas:
        |
        | prepare_for_xml
        | build_xml
        | build_ted
        | sign_xml
        |
        | No tiene relación con el retry de negocio del envío al SII.
        |
        */

        if ($result->shouldRequeueImmediately) {
            self::dispatch(
                $this->documentId
            )
                ->delay(
                    now()->addSeconds(
                        (int) config(
                            'dte.automation.delays.immediate_requeue_seconds',
                            1
                        )
                    )
                )
                ->onConnection(
                    (string) config(
                        'dte.automation.queue_connection'
                    )
                )
                ->onQueue(
                    (string) config(
                        'dte.automation.queues.pipeline'
                    )
                );
        }
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Falló AdvanceDteDocumentPipelineJob.', [
            'document_id' => $this->documentId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
