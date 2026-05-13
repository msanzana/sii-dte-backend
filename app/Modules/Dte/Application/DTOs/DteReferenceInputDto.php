<?php
namespace App\Modules\Dte\Application\DTOs;
final class DteReferenceInputDto
{
    public  function __construct(
        public readonly ?int $referencedDteType = null,
        public readonly ?int $referencedFolio = null,
        public readonly ?string $referencedIssueDate = null,
        public readonly ?int $referencedCode = null,
        public readonly ?string $reason = null,
        public readonly ?array $extraPayload = null,
    )
    {}
}
