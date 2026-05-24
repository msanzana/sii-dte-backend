<?php
namespace App\Modules\Dte\Application\DTOs;
class BuildTedResultDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly string $externalId,
        public readonly int $companyId,
        public readonly int $dteType,
        public readonly int $folio,
        public readonly int $cafId,
        public readonly string $status,
        public readonly string $unsignedZmlPath,
        public readonly string $tedXml,
    )
    {}
}
