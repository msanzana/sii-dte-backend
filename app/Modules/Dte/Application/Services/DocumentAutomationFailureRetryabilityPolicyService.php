<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Exceptions\DomainException;
use Throwable;

final class DocumentAutomationFailureRetryabilityPolicyService
{
    public function shouldRetry(
        Throwable $failure
    ): bool {
        return !(
            $failure instanceof DomainException
        );
    }
}