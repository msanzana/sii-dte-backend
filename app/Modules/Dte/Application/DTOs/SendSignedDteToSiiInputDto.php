<?php
namespace App\Modules\Dte\Application\DTOs;
final class SendSignedDteToSiiInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
