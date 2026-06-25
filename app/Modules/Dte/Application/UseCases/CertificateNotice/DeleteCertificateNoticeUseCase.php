<?php

namespace App\Modules\Dte\Application\UseCases\CertificateNotice;

use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;

final class DeleteCertificateNoticeUseCase
{
    public function __construct(
        private readonly CompanyCertificateNoticeRepositoryInterface $companyCertificateNoticeRepository,
    ) {
    }

    public function execute(int $noticeId): void
    {
        $this->companyCertificateNoticeRepository->delete($noticeId);
    }
}