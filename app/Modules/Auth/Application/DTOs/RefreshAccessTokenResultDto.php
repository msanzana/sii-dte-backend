<?php
namespace App\Modules\Auth\Application\DTOs;

use App\Modules\Auth\Application\DTOs\AccessProfileDto;

final class RefreshAccessTokenResultDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $companyId,
        public readonly string $companyRut,
        public readonly string $companyName,
        public readonly string $accessToken,
        public readonly int $accessExpiresInSeconds,
        public readonly string $refreshToken,
        public readonly int $refreshExpiresInSeconds,
        public readonly AccessProfileDto $accessProfile,
    ){}
}
