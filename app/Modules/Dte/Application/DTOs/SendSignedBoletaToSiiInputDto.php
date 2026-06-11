<?php
namespace App\Modules\Dte\Application\DTOs;

final class SendSignedBoletaToSiiInputDto
{
    public function __construct(
        public readonly int $documentId,
    ) {
    }
}
