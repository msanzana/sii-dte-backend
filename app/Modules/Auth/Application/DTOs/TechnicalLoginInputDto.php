<?php

namespace App\Modules\Auth\Application\DTOs;

final class TechnicalLoginInputDto
{
    public function __construct(
        public readonly string $loginUuid,
        public readonly string $password,
        public readonly ?string $userAgent = null,
        public readonly ?string $ipAddress = null,
    ) {
    }
}
