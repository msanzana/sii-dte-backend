<?php

namespace App\Modules\Dte\Application\UseCases\CertificateNotice;

use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;

final class MarkCertificateNoticeAsReadUseCase
{
    public function __construct(
        private readonly CompanyCertificateNoticeRepositoryInterface $companyCertificateNoticeRepository,
    ) {
    }

    public function execute(int $noticeId): void
    {
        $this->companyCertificateNoticeRepository->markAsRead($noticeId);
    }
}