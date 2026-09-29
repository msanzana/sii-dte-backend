<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;

final class ScheduleDocumentAutomationRetryService
{
    /*
    |--------------------------------------------------------------------------
    | ¿Puede programarse otro retry?
    |--------------------------------------------------------------------------
    |
    | Este método es una fachada sobre DocumentAutomationRetryPolicyService.
    |
    | La intención es que los use cases NO conozcan directamente:
    |
    | - max_attempts
    | - backoff_seconds
    | - lógica de elegibilidad
    |
    */

    public function canScheduleRetry(
        DteDocument $document,
        string $action
    ): bool {
        $policy =
            $this->makePolicy();

        return $policy
            ->canScheduleRetry(
                document:
                    $document,

                action:
                    $action
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Programar retry
    |--------------------------------------------------------------------------
    |
    | Este método:
    |
    | 1. consulta la política;
    | 2. obtiene el delay correspondiente;
    | 3. calcula next_retry_at;
    | 4. crea una nueva instancia de DteDocument;
    | 5. incrementa automation_retry_count;
    | 6. conserva folio, CAF, reserva y referencias de negocio.
    |
    */

    public function execute(
        DteDocument $document,
        string $action,
        string $errorCode,
        string $errorMessage
    ): DteDocument {
        /*
        |--------------------------------------------------------------------------
        | Política
        |--------------------------------------------------------------------------
        */

        $policy =
            $this->makePolicy();

        /*
        |--------------------------------------------------------------------------
        | Delay del próximo retry
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | automation_retry_count = 0
        | → 30 segundos
        |
        | automation_retry_count = 1
        | → 120 segundos
        |
        | automation_retry_count = 2
        | → 300 segundos
        |
        */

        $delaySeconds =
            $policy
                ->nextDelaySeconds(
                    document:
                        $document,

                    action:
                        $action
                );

        /*
        |--------------------------------------------------------------------------
        | Fecha del próximo retry
        |--------------------------------------------------------------------------
        */

        $nextRetryAt =
            now()
                ->addSeconds(
                    $delaySeconds
                )
                ->format(
                    'Y-m-d H:i:s'
                );

        /*
        |--------------------------------------------------------------------------
        | Aplicar transición de dominio
        |--------------------------------------------------------------------------
        */

        return $document
            ->withAutomationRetryScheduled(
                action:
                    $action,

                nextRetryAt:
                    $nextRetryAt,

                errorCode:
                    $errorCode,

                errorMessage:
                    $errorMessage
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Construir política desde configuración
    |--------------------------------------------------------------------------
    |
    | Centralizamos aquí el acceso a config().
    |
    | Los use cases sólo necesitan conocer este servicio.
    |
    */

    private function makePolicy(): DocumentAutomationRetryPolicyService
    {
        return new DocumentAutomationRetryPolicyService(
            maxAttempts:
                (int) config(
                    'dte.automation.document_retry.max_attempts'
                ),

            backoffSeconds:
                (array) config(
                    'dte.automation.document_retry.backoff_seconds',
                    []
                )
        );
    }
}