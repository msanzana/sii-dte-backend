<?php
namespace App\Modules\Dte\Application\DTOs;

final class QuerySiiDocumentStatusInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
