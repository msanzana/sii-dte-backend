<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthUserEloquentModel;

final class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{
    public function findActiveByEmail(
        string $email
    ): ?AuthUser {
        $model = AuthUserEloquentModel::query()
            ->whereRaw(
                'LOWER(email) = ?',
                [$this->normalizeEmail($email)]
            )
            ->where(
                'account_type',
                AuthUser::ACCOUNT_TYPE_HUMAN
            )
            ->where('is_active', true)
            ->first();

        return $model
            ? $this->toDomain($model)
            : null;
    }

    public function findActiveTechnicalByLoginUuid(
        string $loginUuid
    ): ?AuthUser {
        $model = AuthUserEloquentModel::query()
            ->where(
                'login_uuid',
                $this->normalizeLoginUuid($loginUuid)
            )
            ->where(
                'account_type',
                AuthUser::ACCOUNT_TYPE_TECHNICAL
            )
            ->where('is_active', true)
            ->first();

        return $model
            ? $this->toDomain($model)
            : null;
    }

    public function findById(
        int $id
    ): ?AuthUser {
        $model = AuthUserEloquentModel::query()
            ->find($id);

        return $model
            ? $this->toDomain($model)
            : null;
    }

    public function findByEmail(
        string $email
    ): ?AuthUser {
        $model = AuthUserEloquentModel::query()
            ->whereRaw(
                'LOWER(email) = ?',
                [$this->normalizeEmail($email)]
            )
            ->first();

        return $model
            ? $this->toDomain($model)
            : null;
    }

    public function touchLastLoginAt(
        int $userId
    ): void {
        AuthUserEloquentModel::query()
            ->where('id', $userId)
            ->update([
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function findAll(
        ?bool $isActive = null,
        int $limit = 100
    ): array {
        return AuthUserEloquentModel::query()
            ->where(
                'account_type',
                AuthUser::ACCOUNT_TYPE_HUMAN
            )
            ->where('is_visible', true)
            ->when(
                $isActive !== null,
                fn ($query) =>
                    $query->where(
                        'is_active',
                        $isActive
                    )
            )
            ->orderBy('full_name')
            ->limit($limit)
            ->get()
            ->map(
                fn (AuthUserEloquentModel $model) =>
                    $this->toDomain($model)
            )
            ->all();
    }

    public function create(
        string $fullName,
        string $email,
        string $passwordHash,
        bool $isActive = true
    ): AuthUser {
        $model = new AuthUserEloquentModel();

        /*
         * El CRUD administrativo existente crea
         * exclusivamente usuarios humanos.
         *
         * La creación de usuarios técnicos tendrá
         * posteriormente su propio servicio.
         */
        $model->fill([
            'full_name' => $fullName,
            'email' => $this->normalizeEmail($email),

            'account_type' =>
                AuthUser::ACCOUNT_TYPE_HUMAN,

            'login_uuid' => null,

            'password_hash' => $passwordHash,
            'is_active' => $isActive,
            'is_visible' => true,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function update(
        int $userId,
        string $fullName,
        string $email
    ): AuthUser {
        $model = AuthUserEloquentModel::query()
            ->findOrFail($userId);

        $model->fill([
            'full_name' => $fullName,
            'email' => $this->normalizeEmail($email),
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function setPassword(
        int $userId,
        string $passwordHash
    ): void {
        AuthUserEloquentModel::query()
            ->where('id', $userId)
            ->update([
                'password_hash' => $passwordHash,
                'updated_at' => now(),
            ]);
    }

    public function setActive(
        int $userId,
        bool $isActive
    ): void {
        AuthUserEloquentModel::query()
            ->where('id', $userId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    private function toDomain(
        AuthUserEloquentModel $model
    ): AuthUser {
        return new AuthUser(
            id: (int) $model->id,
            fullName: (string) $model->full_name,
            email: (string) $model->email,

            accountType:
                (string) $model->account_type,

            loginUuid:
                $model->login_uuid !== null
                    ? (string) $model->login_uuid
                    : null,

            passwordHash:
                (string) $model->password_hash,

            isActive:
                (bool) $model->is_active,

            isVisible:
                (bool) $model->is_visible,

            lastLoginAt:
                $model->last_login_at
                    ?->format('Y-m-d H:i:s'),
        );
    }

    private function normalizeEmail(
        string $email
    ): string {
        return mb_strtolower(
            trim($email)
        );
    }

    private function normalizeLoginUuid(
        string $loginUuid
    ): string {
        return mb_strtolower(
            trim($loginUuid)
        );
    }
}