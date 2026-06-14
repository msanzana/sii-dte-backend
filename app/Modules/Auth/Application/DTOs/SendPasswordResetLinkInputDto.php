<?php

namespace App\Modules\Auth\Application\DTOs;

final class SendPasswordResetLinkInputDto
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {
    }
}
