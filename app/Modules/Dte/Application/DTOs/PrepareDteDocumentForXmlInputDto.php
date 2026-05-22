<?php
namespace App\Modules\Dte\Application\DTOs;

final class PrepareDteDocumentForXmlInputDto
{
    public function __construct(
        public readonly int $documentId
    )
    {}
}
