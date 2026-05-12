<?php
namespace App\Modules\Dte\Application\DTOs;
final class DteReferenceInputDto
{
    public  function __construct(
        public readonly ?int $referenceDteType = null,
        public readonly ?int $referencedFolio = null,
        public readonly ?string $referencedIssueDate = null,
        public readonly ?int $referenceCode = null,
        public readonly ?string $reason = null,
        public readonly ?array $extraPayload = null,
    )
    {}
}
