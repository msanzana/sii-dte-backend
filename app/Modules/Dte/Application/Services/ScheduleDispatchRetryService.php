<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Services\DispatchRetryPolicyService;

final class ScheduleDispatchRetryService
{
    public function execute(
        SiiDispatch $dispatch
    ): SiiDispatch {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: (int) config(
                'dte.automation.dispatch_retry.max_attempts'
            ),

            backoffSeconds: (array) config(
                'dte.automation.dispatch_retry.backoff_seconds',
                []
            ),
        );

        $delaySeconds =
            $policy->nextDelaySeconds(
                $dispatch
            );

        $nextRetryAt =
            now()
                ->addSeconds(
                    $delaySeconds
                )
                ->format(
                    'Y-m-d H:i:s'
                );

        return $dispatch
            ->withScheduledRetry(
                $nextRetryAt
            );
    }
    public function canScheduleRetry(
        SiiDispatch $dispatch
    ): bool {
        $policy = new DispatchRetryPolicyService(
            maxAttempts: (int) config(
                'dte.automation.dispatch_retry.max_attempts'
            ),

            backoffSeconds: (array) config(
                'dte.automation.dispatch_retry.backoff_seconds',
                []
            ),
        );

        return $policy->canScheduleRetry(
            $dispatch
        );
    }
}