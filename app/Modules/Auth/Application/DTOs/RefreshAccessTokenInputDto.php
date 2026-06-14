<?php

namespace App\Modules\Auth\Application\DTOs;

final class RefreshAccessTokenInputDto
{
    public function __construct(
        public readonly string $refreshToken,
        public readonly ?string $userAgent,
        public readonly ?string $ipAddress,
    ) {
    }
}
