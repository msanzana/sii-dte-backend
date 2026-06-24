<?php
namespace App\Modules\Dte\Application\Mappers;

use App\Modules\Dte\Application\DTOs\CertificateNoticeItemDto;
use App\Modules\Dte\Domain\Entities\CompanyCertificateNotice;

final class CompanyCertificateNoticeMapper
{
    public function toItemDto(CompanyCertificateNotice $notice): CertificateNoticeItemDto
    {
        return new CertificateNoticeItemDto(
            id: $notice->id(),
            companyId: $notice->companyId(),
            userId: $notice->userId(),
            certificateId: $notice->certificateId(),
            source: $notice->source(),
            type: $notice->type(),
            code: $notice->code(),
            title: $notice->title(),
            message: $notice->message(),
            noticeDate: $notice->noticeDate(),
            noticeTime: $notice->noticeTime(),
            emittedAt: $notice->emittedAt(),
            isRead: $notice->isRead(),
            isActive: $notice->isActive(),
        );
    }
}
