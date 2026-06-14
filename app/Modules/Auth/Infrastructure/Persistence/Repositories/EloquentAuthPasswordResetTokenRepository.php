<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\AuthPasswordResetToken;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPasswordResetTokenRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthPasswordResetTokenEloquentModel;


final class EloquentAuthPasswordResetTokenRepository implements AuthPasswordResetTokenRepositoryInterface
{
    public function create(
        int $userId,
        string $tokenHash,
        string $expiresAt,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuthPasswordResetToken {
        $model = new AuthPasswordResetTokenEloquentModel();

        $model->fill([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function findById(int $tokenId): ?AuthPasswordResetToken
    {
        $model = AuthPasswordResetTokenEloquentModel::query()->find($tokenId);

        return $model ? $this->toDomain($model) : null;
    }

    public function markUsed(int $tokenId): void
    {
        AuthPasswordResetTokenEloquentModel::query()
            ->where('id', $tokenId)
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function invalidateAllByUser(int $userId): void
    {
        AuthPasswordResetTokenEloquentModel::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function toDomain(AuthPasswordResetTokenEloquentModel $model): AuthPasswordResetToken
    {
        return new AuthPasswordResetToken(
            id: (int) $model->id,
            userId: (int) $model->user_id,
            tokenHash: (string) $model->token_hash,
            expiresAt: $model->expires_at->format('Y-m-d H:i:s'),
            usedAt: $model->used_at?->format('Y-m-d H:i:s'),
            ipAddress: $model->ip_address,
            userAgent: $model->user_agent,
        );
    }
}