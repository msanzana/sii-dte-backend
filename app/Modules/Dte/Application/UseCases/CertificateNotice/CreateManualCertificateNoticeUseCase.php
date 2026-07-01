<?php

namespace App\Modules\Dte\Application\UseCases\CertificateNotice;

use App\Modules\Dte\Application\DTOs\CreateManualCertificateNoticeInputDto;
use App\Modules\Dte\Application\Services\EmitCertificateNoticeService;

final class CreateManualCertificateNoticeUseCase
{
    public function __construct(
        private readonly EmitCertificateNoticeService $emitCertificateNoticeService,
    ) {
    }

    public function execute(CreateManualCertificateNoticeInputDto $input): void
    {
        $this->emitCertificateNoticeService->manual(
            companyId: $input->companyId,
            userId: $input->userId,
            type: $input->type,
            title: $input->title,
            message: $input->message,
        );
    }
}
