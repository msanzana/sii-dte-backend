<?php
namespace App\Modules\Dte\Application\DTOs;

final class QuerySiiDocumentStatusResultDto
{
    public function __construct(
        public readonly int $documentId,
        public readonly string $externalId,
        public readonly int $companyId,
        public readonly int $dteType,
        public readonly int $folio,
        public readonly string $queriedVia,
        public readonly ?string $siiStatusCode,
        public readonly ?string $siiStatusMessage,
        public readonly ?string $attentionNumber,
        public readonly string $internalStatus,
        public readonly string $rawBody,
    ) {
    }
}
