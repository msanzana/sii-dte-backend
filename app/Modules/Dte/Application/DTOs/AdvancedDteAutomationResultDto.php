<?php
namespace App\Modules\Dte\Application\DTOs;

final class AdvancedDteAutomationResultDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly string $previousStatus,
        public readonly string $currentStatus,
        public readonly ?string $executeAction,
        public readonly bool $shouldRequeueImmediately,
    )
    {}
}
