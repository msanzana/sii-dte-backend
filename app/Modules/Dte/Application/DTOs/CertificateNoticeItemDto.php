<?php
namespace App\Modules\Dte\Application\DTOs;
final class CertificateNoticeItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly ?int $userId,
        public readonly ?int $certificateId,
        public readonly string $source,
        public readonly string $type,
        public readonly ?string $code,
        public readonly string $title,
        public readonly string $message,
        public readonly string $noticeDate,
        public readonly string $noticeTime,
        public readonly string $emittedAt,
        public readonly bool $isRead,
        public readonly bool $isActive,
    ){}
}
