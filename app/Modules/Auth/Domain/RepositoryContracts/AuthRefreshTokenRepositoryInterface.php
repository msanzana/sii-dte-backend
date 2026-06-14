<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthRefreshToken;

interface AuthRefreshTokenRepositoryInterface
{
    public function create(
        int $userId,
        int $companyId,
        string $tokenHash,
        string $expiresAt,
        ?string $userAgent = null,
        ?string $ipAddress = null
    ): AuthRefreshToken;

    public function findById(int $refreshTokenId): ?AuthRefreshToken;

    public function revoke(int $refreshTokenId): void;

    public function revokeAllByUserAndCompany(int $userId, int $companyId): void;

    public function revokeAllByUser(int $userId): void;
}
