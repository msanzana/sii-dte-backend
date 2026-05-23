<?php
namespace App\Modules\Dte\Application\DTOs;
final class BuildDteXmlResultDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly string $externalId,
        public readonly int $companyId,
        public readonly int $dteType,
        public readonly int $folio,
        public readonly string $documentXmlId,
        public readonly string $status,
        public readonly string $siiEnvironment,
        public readonly string $unsignedXmlPath,
    ){}
}
