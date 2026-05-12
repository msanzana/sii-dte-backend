<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateDteDocumentInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $dteType,
        public readonly string $issueDate,
        public readonly ReceiverInputDto $receiver,
        public readonly array $items,
        public readonly array $references = [],
        public readonly ?string $externalId = null,
        public readonly ?array $headerPayload = null,
        public readonly ?array $rawInput = null,
    )
    {}
}
