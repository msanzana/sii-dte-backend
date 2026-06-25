<?php

namespace App\Modules\Dte\Application\UseCases\CertificateNotice;

use App\Modules\Dte\Application\DTOs\ListCompanyCertificateNoticesResultDto;
use App\Modules\Dte\Application\Mappers\CompanyCertificateNoticeMapper;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyCertificateNoticeRepositoryInterface;

final class ListCompanyCertificateNoticesUseCase
{
    public function __construct(
        private readonly CompanyCertificateNoticeRepositoryInterface $companyCertificateNoticeRepository,
        private readonly CompanyCertificateNoticeMapper $companyCertificateNoticeMapper,
    ) {
    }

    public function execute(int $companyId): ListCompanyCertificateNoticesResultDto
    {
        $items = $this->companyCertificateNoticeRepository->findByCompanyId($companyId, 200);

        return new ListCompanyCertificateNoticesResultDto(
            items: array_map(
                fn ($item) => $this->companyCertificateNoticeMapper->toItemDto($item),
                $items
            )
        );
    }
}