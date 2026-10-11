<?php

namespace App\Modules\Auth\Application\DTOs;

final class TechnicalRefreshAccessTokenResultDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $companyId,
        public readonly string $companyRut,
        public readonly string $companyName,
        public readonly int $externalSystemId,
        public readonly string $externalSystemCode,
        public readonly string $externalSystemName,
        public readonly string $accessToken,
        public readonly int $accessExpiresInSeconds,
        public readonly string $refreshToken,
        public readonly int $refreshExpiresInSeconds,
    ) {
    }
}
