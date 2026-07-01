<?php

namespace App\Modules\Dte\Application\UseCases\CertificateNotice;

use App\Modules\Dte\Application\DTOs\UpdateManualCertificateNoticeInputDto;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;

final class UpdateManualCertificateNoticeUseCase
{
    public function __construct(
        private readonly CompanyCertificateNoticeRepositoryInterface $companyCertificateNoticeRepository,
    ) {
    }

    public function execute(UpdateManualCertificateNoticeInputDto $input): void
    {
        $this->companyCertificateNoticeRepository->updateManualNotice(
            noticeId: $input->noticeId,
            type: $input->type,
            title: $input->title,
            message: $input->message,
            isActive: $input->isActive,
        );
    }
}
