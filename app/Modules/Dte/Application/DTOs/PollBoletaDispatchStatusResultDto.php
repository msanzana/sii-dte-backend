<?php
namespace App\Modules\Dte\Application\DTOs;

final class PollBoletaDispatchStatusResultDto
{
    public function __construct(
        public readonly int $dispatchId,
        public readonly string $status,
        public readonly ?string $trackId,
        public readonly ?string $sendStatusCode,
        public readonly ?string $sendStatusMessage,
        public readonly string $rawBody,
    ) {
    }
}
