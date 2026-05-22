<?php
namespace App\Modules\Dte\Application\DTOs;
final class PrepareDteDocumentForXmlResultDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly string $externalId,
        public readonly int $companyId,
        public readonly int $dteType,
        public readonly int $cafId,
        public readonly int $folio,
        public readonly int $cafFolioStart,
        public readonly int $cafFolioEnd,
        public readonly string $status,
        public readonly string $siiEnvironment,
    )
    {}
}
