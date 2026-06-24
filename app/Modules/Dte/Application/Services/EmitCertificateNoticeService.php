<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;

final class EmitCertificateNoticeService
{
    public function __construct(
        private readonly CompanyCertificateNoticeRepositoryInterface $companyCertificateNoticeRepository,
    ){}

    public function automatic(
        int $companyId,
        ?int $certificateId,
        string $type,
        string $code,
        string $title,
        string $message
    ): void
    {
        $this->companyCertificateNoticeRepository->create(
            companyId: $companyId,
            userId: null,
            certificateId: $certificateId,
            source: 'automatic',
            type: $type,
            code: $code,
            title: $title,
            message: $message
        );
    }

    public function manual(
        int $companyId,
        int $userId,
        string $type,
        string $title,
        string $message
    ): void
    {
        $this->companyCertificateNoticeRepository->create(
            companyId: $companyId,
            userId: $userId,
            certificateId: null,
            source: 'manual',
            type: $type,
            code: null,
            title: $title,
            message: $message
        );
    }
}
