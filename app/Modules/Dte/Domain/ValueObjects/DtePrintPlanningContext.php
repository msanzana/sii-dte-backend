<?php

namespace App\Modules\Dte\Domain\ValueObjects;

final class DtePrintPlanningContext
{
    public function __construct(
        private readonly bool $includeClientCopy = false,
    ) {}

    public function includeClientCopy(): bool
    {
        return $this->includeClientCopy;
    }
}