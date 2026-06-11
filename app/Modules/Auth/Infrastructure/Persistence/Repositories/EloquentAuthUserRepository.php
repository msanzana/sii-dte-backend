<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthUserEloquentModel;

final class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{

    public function findActiveByEmail(string $email): ?AuthUser
    {
        $model = AuthUserEloquentModel::query()
                ->whereRaw("LOWER(email = ?)", [mb_strtolower(trim($email))])
                ->where('is_active',true)
                ->first();

        if(!$model)
        {
            return null;
        }
        return $this->toDomain($model);
    }

    public function findById(int $id): ?AuthUser
    {
        $model = AuthUserEloquentModel::query()->find($id);

        if(!$model)
        {
            return null;
        }

        return $this->toDomain($model);
    }
    public function touchLastLoginAt(int $userId): void
    {
        AuthUserEloquentModel::query()
            ->where('id', $userId)
            ->update([
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function toDomain(AuthUserEloquentModel $model): AuthUser
    {
        return new AuthUser(
            id: (int) $model->id,
            fullName: (string) $model->full_name,
            email: (string) $model->email,
            passwordHash: (string) $model->password_hash,
            isActive: (bool) $model->is_active,
            lastLoginAt: $model->last_login_at?->format('Y-m-d H:i:s'),
        );
    }
}
