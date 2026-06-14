<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthPasswordResetToken;

interface AuthPasswordResetTokenRepositoryInterface
{
    public function create(
        int $userId,
        string $tokenHash,
        string $expiresAt,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuthPasswordResetToken;

    public function findById(int $tokenId): ?AuthPasswordResetToken;

    public function markUsed(int $tokenId): void;

    public function invalidateAllByUser(int $userId): void;
}
