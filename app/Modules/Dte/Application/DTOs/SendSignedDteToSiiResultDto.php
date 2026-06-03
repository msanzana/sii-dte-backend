<?php
namespace App\Modules\Dte\Application\DTOs;
final class SendSignedDteToSiiResultDto
{
    public function __construct(
        public readonly int $dispatchId,
        public readonly int $documentId,
        public readonly string $batchUuid,
        public readonly string $status,
        public readonly ?string $trackId,
        public readonly ?string $uploadStatusCode,
        public readonly ?string $uploadStatusMessage,
        public readonly string $requestBodyPath,
    )
    {}
}
