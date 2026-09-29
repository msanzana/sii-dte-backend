<?php

namespace App\Modules\Dte\Application\Exceptions;

use RuntimeException;
use Throwable;

final class DocumentAutomationNotRetryableException
    extends RuntimeException
{
    public function __construct(
        private readonly int $documentId,
        private readonly string $action,
        Throwable $previous
    ) {
        parent::__construct(
            $previous->getMessage(),
            (int) $previous->getCode(),
            $previous
        );
    }

    public function documentId(): int
    {
        return $this->documentId;
    }

    public function action(): string
    {
        return $this->action;
    }
}
