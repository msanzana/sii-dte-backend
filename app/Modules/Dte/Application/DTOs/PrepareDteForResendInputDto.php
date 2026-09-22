<?php

namespace App\Modules\Dte\Application\DTOs;

final class PrepareDteForResendInputDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly array $headerPayloadPatch,
    ) {
    }
}