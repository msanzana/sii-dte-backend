<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateDteDocumentResultDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $externalId,
        public readonly string $status,
        public readonly int $dteType,
        public readonly string $issueDate,
        public readonly ?int $receiverCityId,
        public readonly float $netAmount,
        public readonly float $exemptAmount,
        public readonly float $taxAmount,
        public readonly float $totalAmount,
    )
    {}
}
