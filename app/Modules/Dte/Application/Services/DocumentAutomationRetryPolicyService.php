<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\DteDocument;
use DateTimeImmutable;
use LogicException;
use Throwable;

final class DocumentAutomationRetryPolicyService
{
    /*
    |--------------------------------------------------------------------------
    | Acciones internas elegibles para retry automático
    |--------------------------------------------------------------------------
    |
    | Estas acciones ocurren ANTES de contactar al SII.
    |
    | Por lo tanto, un retry de estas operaciones no implica por sí mismo
    | el riesgo de reenviar un documento que pudo haber llegado al SII.
    |
    */

    private const RETRYABLE_ACTIONS = [
        'build_xml',
        'build_ted',
        'sign_xml',
    ];

    public function __construct(
        private readonly int $maxAttempts,
        private readonly array $backoffSeconds,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | ¿Se puede programar otro retry?
    |--------------------------------------------------------------------------
    |
    | Esta función responde únicamente si todavía podemos CREAR una nueva
    | programación de retry.
    |
    | Ejemplo con maxAttempts = 3:
    |
    | retry_count = 0  -> sí
    | retry_count = 1  -> sí
    | retry_count = 2  -> sí
    | retry_count = 3  -> no
    |
    */

    public function canScheduleRetry(
        DteDocument $document,
        string $action
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Sólo determinadas etapas internas son retryables
        |--------------------------------------------------------------------------
        */

        if (!$this->isRetryableAction($action)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Protección ante una acción pendiente diferente
        |--------------------------------------------------------------------------
        |
        | Si el documento ya tiene un retry pendiente para build_xml,
        | no debemos reutilizar ese mismo contador para build_ted o sign_xml.
        |
        | Normalmente una transición exitosa de etapa limpiará posteriormente
        | este estado, pero dejamos aquí igualmente la protección de dominio.
        |
        */

        $scheduledAction =
            $document->automationRetryAction();

        if (
            $scheduledAction !== null
            && $scheduledAction !== ''
            && $scheduledAction !== $action
            && $document->automationRetryCount() > 0
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Límite máximo
        |--------------------------------------------------------------------------
        */

        return $document->automationRetryCount()
            < $this->maxAttempts;
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener backoff del próximo retry
    |--------------------------------------------------------------------------
    |
    | El índice utilizado corresponde al número de retries YA programados.
    |
    | retry_count = 0 -> backoff[0]
    | retry_count = 1 -> backoff[1]
    | retry_count = 2 -> backoff[2]
    |
    */

    public function nextDelaySeconds(
        DteDocument $document,
        string $action
    ): int {
        if (
            !$this->canScheduleRetry(
                document: $document,
                action: $action
            )
        ) {
            throw new LogicException(
                'El documento no es elegible para programar otro retry de automatización.'
            );
        }

        $retryCount =
            $document->automationRetryCount();

        /*
        |--------------------------------------------------------------------------
        | Protección de configuración
        |--------------------------------------------------------------------------
        |
        | Por ejemplo:
        |
        | maxAttempts = 3
        |
        | pero accidentalmente sólo se configuró:
        |
        | [30, 120]
        |
        | En ese caso no inventamos un delay.
        |
        */

        if (
            !array_key_exists(
                $retryCount,
                $this->backoffSeconds
            )
        ) {
            throw new LogicException(
                'No existe un backoff configurado para el próximo retry de automatización.'
            );
        }

        return (int) $this->backoffSeconds[
            $retryCount
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ¿Puede ejecutarse un retry que YA fue programado?
    |--------------------------------------------------------------------------
    |
    | Esta función NO pregunta si podemos crear otro retry.
    |
    | Pregunta si uno que ya existe:
    |
    | - tiene una acción válida;
    | - coincide con la acción solicitada;
    | - tiene retry_count válido;
    | - tiene next_retry_at válido;
    | - y su plazo ya venció.
    |
    */

    public function canExecuteScheduledRetry(
        DteDocument $document,
        string $action,
        DateTimeImmutable $now
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Acción solicitada permitida
        |--------------------------------------------------------------------------
        */

        if (!$this->isRetryableAction($action)) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Debe existir al menos un retry ya programado
        |--------------------------------------------------------------------------
        */

        if (
            $document->automationRetryCount() < 1
            || $document->automationRetryCount()
                > $this->maxAttempts
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | La acción programada debe coincidir
        |--------------------------------------------------------------------------
        */

        if (
            $document->automationRetryAction()
            !== $action
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Debe existir next_retry_at
        |--------------------------------------------------------------------------
        */

        $nextRetryAt =
            $document->automationNextRetryAt();

        if (
            $nextRetryAt === null
            || trim($nextRetryAt) === ''
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | La fecha debe ser válida
        |--------------------------------------------------------------------------
        */

        try {
            $scheduledAt =
                new DateTimeImmutable(
                    $nextRetryAt
                );
        } catch (Throwable) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | El retry puede ejecutarse al llegar o superar la fecha
        |--------------------------------------------------------------------------
        */

        return $scheduledAt <= $now;
    }

    /*
    |--------------------------------------------------------------------------
    | Acción retryable
    |--------------------------------------------------------------------------
    */

    private function isRetryableAction(
        string $action
    ): bool {
        return in_array(
            $action,
            self::RETRYABLE_ACTIONS,
            true
        );
    }
}