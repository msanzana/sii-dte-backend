<?php

namespace App\Modules\Dte\Domain\Services;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use LogicException;
use DateTimeImmutable;

final class DispatchRetryPolicyService
{
    public function __construct(
        private readonly int $maxAttempts,
        private readonly array $backoffSeconds,
    ) {
    }

    public function canScheduleRetry(
        SiiDispatch $dispatch
    ): bool {
        return $dispatch->status() === DispatchStatus::FAILED->value
            && $dispatch->retryCount() < $this->maxAttempts;
    }

    public function nextDelaySeconds(
        SiiDispatch $dispatch
    ): int {
        if (!$this->canScheduleRetry($dispatch)) {
            throw new LogicException(
                'El dispatch no es elegible para retry automático.'
            );
        }

        $retryCount = $dispatch->retryCount();

        if (!array_key_exists($retryCount, $this->backoffSeconds)) {
            throw new LogicException(
                'No existe un backoff configurado para el próximo retry.'
            );
        }

        return (int) $this->backoffSeconds[$retryCount];
    }
    public function canExecuteScheduledRetry(
        SiiDispatch $dispatch,
        DateTimeImmutable $now
    ): bool {
        if (
            $dispatch->status()
            !== DispatchStatus::FAILED->value
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | retry_count representa un retry YA PROGRAMADO
        |--------------------------------------------------------------------------
        |
        | 1 = primer retry programado
        | 2 = segundo retry programado
        | 3 = tercer retry programado
        |
        | Por eso retry_count == maxAttempts todavía puede ejecutarse.
        |
        */
        if (
            $dispatch->retryCount() < 1
            || $dispatch->retryCount() > $this->maxAttempts
        ) {
            return false;
        }

        $nextRetryAt =
            $dispatch->nextRetryAt();

        if (
            $nextRetryAt === null
            || trim($nextRetryAt) === ''
        ) {
            return false;
        }

        try {
            $scheduledAt =
                new DateTimeImmutable(
                    $nextRetryAt
                );
        } catch (\Throwable) {
            return false;
        }

        return $scheduledAt <= $now;
    }
    public function hasScheduledRetry(
        SiiDispatch $dispatch
    ): bool {
        if (
            $dispatch->status()
            !== DispatchStatus::FAILED->value
        ) {
            return false;
        }

        if (
            $dispatch->retryCount() < 1
            || $dispatch->retryCount() > $this->maxAttempts
        ) {
            return false;
        }

        $nextRetryAt =
            $dispatch->nextRetryAt();

        if (
            $nextRetryAt === null
            || trim($nextRetryAt) === ''
        ) {
            return false;
        }

        try {
            new DateTimeImmutable(
                $nextRetryAt
            );
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}