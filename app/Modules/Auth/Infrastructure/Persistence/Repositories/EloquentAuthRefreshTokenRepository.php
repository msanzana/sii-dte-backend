<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\AuthRefreshToken;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthRefreshTokenEloquentModel;

final class EloquentAuthRefreshTokenRepository implements AuthRefreshTokenRepositoryInterface
{
    public function create(
        int $userId,
        int $companyId,
        string $tokenHash,
        string $expiresAt,
        ?string $userAgent = null,
        ?string $ipAddress = null
    ): AuthRefreshToken {
        $model = new AuthRefreshTokenEloquentModel();

        $model->fill([
            'user_id' => $userId,
            'company_id' => $companyId,
            'token_hash' => $tokenHash,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function findById(int $refreshTokenId): ?AuthRefreshToken
    {
        $model = AuthRefreshTokenEloquentModel::query()->find($refreshTokenId);

        return $model ? $this->toDomain($model) : null;
    }

    public function revoke(int $refreshTokenId): void
    {
        AuthRefreshTokenEloquentModel::query()
            ->where('id', $refreshTokenId)
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function revokeAllByUserAndCompany(int $userId, int $companyId): void
    {
        AuthRefreshTokenEloquentModel::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function revokeAllByUser(int $userId): void
    {
        AuthRefreshTokenEloquentModel::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function toDomain(AuthRefreshTokenEloquentModel $model): AuthRefreshToken
    {
        return new AuthRefreshToken(
            id: (int) $model->id,
            userId: (int) $model->user_id,
            companyId: (int) $model->company_id,
            tokenHash: (string) $model->token_hash,
            userAgent: $model->user_agent,
            ipAddress: $model->ip_address,
            expiresAt: $model->expires_at->format('Y-m-d H:i:s'),
            revokedAt: $model->revoked_at?->format('Y-m-d H:i:s'),
        );
    }
}
