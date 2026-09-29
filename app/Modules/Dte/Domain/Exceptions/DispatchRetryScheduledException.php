<?php

namespace App\Modules\Dte\Domain\Exceptions;

use RuntimeException;
use Throwable;

final class DispatchRetryScheduledException extends RuntimeException
{
    public function __construct(
        private readonly int $dispatchId,
        private readonly int $documentId,
        Throwable $previous
    ) {
        parent::__construct(
            message: $previous->getMessage(),
            code: (int) $previous->getCode(),
            previous: $previous
        );
    }

    public static function forScheduledRetry(
        int $dispatchId,
        int $documentId,
        Throwable $previous
    ): self {
        return new self(
            dispatchId: $dispatchId,
            documentId: $documentId,
            previous: $previous
        );
    }

    public function dispatchId(): int
    {
        return $this->dispatchId;
    }

    public function documentId(): int
    {
        return $this->documentId;
    }
}