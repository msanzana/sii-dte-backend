<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\CompanyCertificateNotice;

interface CompanyCertificateNoticeRepositoryInterface
{
    public function findByCompanyId(int $companyId, int $limit = 100):array;
    public function findById(int $noticeId): ?CompanyCertificateNotice;
    public function create(
        int $companyId,
        ?int $userId,
        ?int  $certificateId,
        string $source,
        string $type,
        ?string $code,
        string $title,
        string $message
    ): CompanyCertificateNotice;

    public function updateManualNotice(
        int $noticeId,
        string $type,
        string $title,
        string $message,
        bool $isActive
    ): CompanyCertificateNotice;
    public function markAsRead(int $noticeId): void;
    public function delete(int $noticeId): void;
}
