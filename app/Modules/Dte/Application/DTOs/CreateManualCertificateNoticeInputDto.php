<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateManualCertificateNoticeInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $userId,
        public readonly string $type,
        public readonly string $tittle,
        public readonly string $message,
    ) {
    }
}
