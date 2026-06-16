<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\AuthPermission;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPermissionRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthPermissionEloquentModel;

final class EloquentAuthPermissionRepository implements AuthPermissionRepositoryInterface
{
    /**
     * @return AuthPermission[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array
    {
        return AuthPermissionEloquentModel::query()
            ->when(
                $isActive !== null,
                fn ($query) => $query->where('is_active', $isActive)
            )
            ->orderBy('module')
            ->orderBy('code')
            ->limit($limit)
            ->get()
            ->map(fn (AuthPermissionEloquentModel $model) => $this->toDomain($model))
            ->all();
    }

    public function findById(int $permissionId): ?AuthPermission
    {
        $model = AuthPermissionEloquentModel::query()->find($permissionId);

        return $model ? $this->toDomain($model) : null;
    }

    public function create(
        string $code,
        string $name,
        string $module,
        ?string $description,
        bool $isActive
    ): AuthPermission {
        $model = new AuthPermissionEloquentModel();

        $model->fill([
            'code' => trim($code),
            'name' => trim($name),
            'module' => trim($module),
            'description' => $description !== null ? trim($description) : null,
            'is_active' => $isActive,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function update(
        int $permissionId,
        string $code,
        string $name,
        string $module,
        ?string $description
    ): AuthPermission {
        $model = AuthPermissionEloquentModel::query()->findOrFail($permissionId);

        $model->fill([
            'code' => trim($code),
            'name' => trim($name),
            'module' => trim($module),
            'description' => $description !== null ? trim($description) : null,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function setActive(int $permissionId, bool $isActive): void
    {
        AuthPermissionEloquentModel::query()
            ->where('id', $permissionId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    private function toDomain(AuthPermissionEloquentModel $model): AuthPermission
    {
        return new AuthPermission(
            id: (int) $model->id,
            code: (string) $model->code,
            name: (string) $model->name,
            module: (string) $model->module,
            description: $model->description,
            isActive: (bool) $model->is_active,
        );
    }
}
