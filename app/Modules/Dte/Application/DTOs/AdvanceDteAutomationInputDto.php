<?php
namespace App\Modules\Dte\Application\DTOs;

final class AdvanceDteAutomationInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
