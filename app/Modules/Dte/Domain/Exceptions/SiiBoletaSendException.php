<?php

namespace App\Modules\Dte\Domain\Exceptions;

class SiiBoletaSendException extends DomainException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        private readonly ?string $rawBody = null,
    ) {
        parent::__construct($message);
    }

    public static function because(
        string $message,
        ?int $httpStatus = null,
        ?string $rawBody = null,
    ): self {
        return new self(
            message: $message,
            httpStatus: $httpStatus,
            rawBody: $rawBody,
        );
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function rawBody(): ?string
    {
        return $this->rawBody;
    }
}
