<?php
namespace App\Modules\Dte\Application\DTOs;

final class UpdateManualCertificateNoticeInputDto
{
    public function __construct(
        public readonly int $noticeId,
        public readonly string $type,
        public readonly string $tittle,
        public readonly string $message,
        public readonly bool $isActive,
    ){}
}
