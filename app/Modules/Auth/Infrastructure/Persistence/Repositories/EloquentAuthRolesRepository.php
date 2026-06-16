<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\AuthRole;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRolesRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthRoleEloquentModel;
use Illuminate\Support\Facades\DB;

final class EloquentAuthRolesRepository implements AuthRolesRepositoryInterface
{
    /**
     * @return AuthRole[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array
    {
        return AuthRoleEloquentModel::query()
            ->when(
                $isActive !== null,
                fn($query) => $query->where('is_active', $isActive)
            )
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn(AuthRoleEloquentModel $model) => $this->toDomain($model))
            ->all();
    }

    public function findById(int $roleId): ?AuthRole
    {
        $model = AuthRoleEloquentModel::query()->find($roleId);

        return $model ? $this->toDomain($model) : null;
    }

    public function create(
        string $code,
        string $name,
        ?string $description,
        bool $isSystem,
        bool $isActive
    ): AuthRole {
        $model = new AuthRoleEloquentModel();

        $model->fill([
            'code' => trim($code),
            'name' => trim($name),
            'description' => $description !== null ? trim($description) : null,
            'is_system' => $isSystem,
            'is_active' => $isActive,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function update(
        int $roleId,
        string $code,
        string $name,
        ?string $description
    ): AuthRole {
        $model = AuthRoleEloquentModel::query()->findOrFail($roleId);

        $model->fill([
            'code' => trim($code),
            'name' => trim($name),
            'description' => $description !== null ? trim($description) : null,
        ]);

        $model->save();

        return $this->toDomain($model);
    }

    public function setActive(int $roleId, bool $isActive): void
    {
        AuthRoleEloquentModel::query()
            ->where('id', $roleId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param int[] $permissionIds
     */
    public function syncPermissionIds(int $roleId, array $permissionIds): void
    {
        DB::transaction(function () use ($roleId, $permissionIds) {
            DB::table('auth_role_permissions')
                ->where('role_id', $roleId)
                ->delete();

            $rows = [];
            $now = now();

            foreach (array_unique(array_map(fn($id) => (int) $id, $permissionIds)) as $permissionId) {
                if ($permissionId <= 0) {
                    continue;
                }

                $rows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('auth_role_permissions')->insert($rows);
            }
        });
    }

    /**
     * @return int[]
     */
    public function findPermissionIdsByRoleId(int $roleId): array
    {
        return DB::table('auth_role_permissions')
            ->where('role_id', $roleId)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->map(fn($value) => (int) $value)
            ->all();
    }
    public function findPermissionCodesByRoleId(int $roleId): array
    {
        return DB::table('auth_role_permissions as arp')
            ->join('auth_permissions as ap', 'ap.id', '=', 'arp.permission_id')
            ->where('arp.role_id', $roleId)
            ->where('ap.is_active', true)
            ->distinct()
            ->orderBy('ap.code')
            ->pluck('ap.code')
            ->map(fn($value) => (string) $value)
            ->all();
    }

    private function toDomain(AuthRoleEloquentModel $model): AuthRole
    {
        return new AuthRole(
            id: (int) $model->id,
            code: (string) $model->code,
            name: (string) $model->name,
            description: $model->description,
            isSystem: (bool) $model->is_system,
            isActive: (bool) $model->is_active,
            permissionCodes: $this->findPermissionCodesByRoleId((int) $model->id),
            permissionIds: $this->findPermissionIdsByRoleId((int) $model->id),
        );
    }


    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        DB::transaction(function () use ($roleId, $permissionIds) {
        DB::table('auth_role_permissions')
            ->where('role_id', $roleId)
            ->delete();

        $rows = [];
        $now = now();

        foreach (array_unique(array_map(fn ($id) => (int) $id, $permissionIds)) as $permissionId) {
            if ($permissionId <= 0) {
                continue;
            }

            $rows[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('auth_role_permissions')->insert($rows);
        }
    });
    }
}
