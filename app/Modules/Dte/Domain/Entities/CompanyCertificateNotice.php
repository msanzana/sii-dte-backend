<?php

namespace App\Modules\Dte\Domain\Entities;

final class CompanyCertificateNotice
{
    public function __construct(
        private readonly int $id,
        private readonly int $companyId,
        private readonly ?int $userId,
        private readonly ?int $certificateId,
        private readonly string $source,
        private readonly string $type,
        private readonly ?string $code,
        private readonly string $title,
        private readonly string $message,
        private readonly string $noticeDate,
        private readonly string $noticeTime,
        private readonly string $emittedAt,
        private readonly bool $isRead,
        private readonly bool $isActive,
    ){}

    public function id():int
    {
        return $this->id;
    }

    public function companyId():int
    {
        return $this->companyId;
    }

    public function userId():?int
    {
        return $this->userId;
    }

    public function certificateId():?int
    {
        return $this->certificateId;
    }

    public function source():string
    {
        return $this->source;
    }

    public function type():string
    {
        return $this->type;
    }

    public function code():?string
    {
        return $this->code;
    }
    public function title():string
    {
        return $this->title;
    }

    public function message():string
    {
        return $this->message;
    }

    public function noticeDate():string
    {
        return $this->noticeDate;
    }

    public function noticeTime():string
    {
        return $this->noticeTime;
    }

    public function emittedAt():string
    {
        return $this->emittedAt;
    }

    public function isRead():bool
    {
        return $this->isRead;
    }

    public function isActive():bool
    {
        return $this->isActive;
    }
}
