<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateDteDocumentResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $httpStatus = null,
        public readonly ?string $rawBody = null,
        public readonly ?string $trackId = null,
        public readonly ?string $statusCode = null,
        public readonly ?string $statusMessage = null,
        public readonly ?string $error = null,

    )
    {}
}
