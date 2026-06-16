<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\UserCompanyAccess;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthUserCompanyAccessEloquentModel;
use App\Modules\Auth\Infrastructure\Persistence\EloquentModels\AuthUserCompanyRoleEloquentModel;
use Illuminate\Support\Facades\DB;

final class EloquentUserCompanyAccessRepository implements UserCompanyAccessRepositoryInterface
{
    public function findAccessibleCompaniesByUserId(int $userId): array
    {
        $rows = DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.user_id', $userId)
            ->where('auca.is_active', true)
            ->where('c.is_active', true)
            ->orderByDesc('auca.is_default')
            ->orderBy('c.legal_name')
            ->select([
                'auca.id as access_id',
                'auca.user_id',
                'auca.company_id',
                'auca.is_active',
                'auca.is_default',
                'auca.can_select_company',
                'c.rut as company_rut',
                'c.legal_name as company_name',
            ])
            ->get();

        return array_map(fn ($row) => $this->rowToDomain($row), $rows->all());
    }

    public function findAccessibleCompanyByUserAndCompany(
        int $userId,
        int $companyId
    ): ?UserCompanyAccess {
        $row = DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.user_id', $userId)
            ->where('auca.company_id', $companyId)
            ->where('auca.is_active', true)
            ->where('c.is_active', true)
            ->select([
                'auca.id as access_id',
                'auca.user_id',
                'auca.company_id',
                'auca.is_active',
                'auca.is_default',
                'auca.can_select_company',
                'c.rut as company_rut',
                'c.legal_name as company_name',
            ])
            ->first();

        return $row ? $this->rowToDomain($row) : null;
    }

    public function findAssignmentsByUserId(int $userId): array
    {
        $rows = DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.user_id', $userId)
            ->orderByDesc('auca.is_default')
            ->orderBy('c.legal_name')
            ->select([
                'auca.id as access_id',
                'auca.user_id',
                'auca.company_id',
                'auca.is_active',
                'auca.is_default',
                'auca.can_select_company',
                'c.rut as company_rut',
                'c.legal_name as company_name',
            ])
            ->get();

        return array_map(fn ($row) => $this->rowToDomain($row), $rows->all());
    }

    public function findByAccessId(int $accessId): ?UserCompanyAccess
    {
        $row = DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.id', $accessId)
            ->select([
                'auca.id as access_id',
                'auca.user_id',
                'auca.company_id',
                'auca.is_active',
                'auca.is_default',
                'auca.can_select_company',
                'c.rut as company_rut',
                'c.legal_name as company_name',
            ])
            ->first();

        return $row ? $this->rowToDomain($row) : null;
    }

    public function assignCompanyToUser(
        int $userId,
        int $companyId,
        bool $isDefault,
        bool $canSelectCompany,
        array $roleIds
    ): UserCompanyAccess {
        return DB::transaction(function () use ($userId, $companyId, $isDefault, $canSelectCompany, $roleIds) {
            if ($isDefault) {
                AuthUserCompanyAccessEloquentModel::query()
                    ->where('user_id', $userId)
                    ->update([
                        'is_default' => false,
                        'updated_at' => now(),
                    ]);
            }

            $access = new AuthUserCompanyAccessEloquentModel();

            $access->fill([
                'user_id' => $userId,
                'company_id' => $companyId,
                'is_active' => true,
                'is_default' => $isDefault,
                'can_select_company' => $canSelectCompany,
            ]);

            $access->save();

            $this->syncRoleIdsByAccessId((int) $access->id, $roleIds);

            return $this->findByAccessId((int) $access->id);
        });
    }

    public function updateAssignment(
        int $accessId,
        bool $isDefault,
        bool $canSelectCompany,
        array $roleIds
    ): UserCompanyAccess {
        return DB::transaction(function () use ($accessId, $isDefault, $canSelectCompany, $roleIds) {
            $model = AuthUserCompanyAccessEloquentModel::query()->findOrFail($accessId);

            if ($isDefault) {
                AuthUserCompanyAccessEloquentModel::query()
                    ->where('user_id', $model->user_id)
                    ->where('id', '!=', $accessId)
                    ->update([
                        'is_default' => false,
                        'updated_at' => now(),
                    ]);
            }

            $model->fill([
                'is_default' => $isDefault,
                'can_select_company' => $canSelectCompany,
            ]);

            $model->save();

            $this->syncRoleIdsByAccessId($accessId, $roleIds);

            return $this->findByAccessId($accessId);
        });
    }

    public function setAssignmentActive(int $accessId, bool $isActive): void
    {
        AuthUserCompanyAccessEloquentModel::query()
            ->where('id', $accessId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    public function removeAssignment(int $accessId): void
    {
        AuthUserCompanyRoleEloquentModel::query()
            ->where('user_company_access_id', $accessId)
            ->delete();

        AuthUserCompanyAccessEloquentModel::query()
            ->where('id', $accessId)
            ->delete();
    }

    public function hasActiveAccess(int $userId, int $companyId): bool
    {
        return DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.user_id', $userId)
            ->where('auca.company_id', $companyId)
            ->where('auca.is_active', true)
            ->where('c.is_active', true)
            ->exists();
    }

    private function syncRoleIdsByAccessId(int $accessId, array $roleIds): void
    {
        AuthUserCompanyRoleEloquentModel::query()
            ->where('user_company_access_id', $accessId)
            ->delete();

        foreach (array_unique(array_map(fn ($id) => (int) $id, $roleIds)) as $roleId) {
            AuthUserCompanyRoleEloquentModel::query()->create([
                'user_company_access_id' => $accessId,
                'role_id' => $roleId,
            ]);
        }
    }

    private function rowToDomain(object $row): UserCompanyAccess
    {
        return new UserCompanyAccess(
            accessId: (int) $row->access_id,
            userId: (int) $row->user_id,
            companyId: (int) $row->company_id,
            companyRut: (string) $row->company_rut,
            companyName: (string) $row->company_name,
            isActive: (bool) $row->is_active,
            isDefault: (bool) $row->is_default,
            canSelectCompany: (bool) $row->can_select_company,
            roleCodes: $this->findRoleCodesByAccessId((int) $row->access_id),
            permissionCodes: $this->findPermissionCodesByAccessId((int) $row->access_id),
            permissionItems: $this->findPermissionItemsByAccessId((int) $row->access_id),
        );
    }

    /**
     * @return string[]
     */
    private function findRoleCodesByAccessId(int $accessId): array
    {
        return DB::table('auth_user_company_roles as aucr')
            ->join('auth_roles as ar', 'ar.id', '=', 'aucr.role_id')
            ->where('aucr.user_company_access_id', $accessId)
            ->where('ar.is_active', true)
            ->orderBy('ar.code')
            ->pluck('ar.code')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * @return string[]
     */
    private function findPermissionCodesByAccessId(int $accessId): array
    {
        return DB::table('auth_user_company_roles as aucr')
            ->join('auth_roles as ar', 'ar.id', '=', 'aucr.role_id')
            ->join('auth_role_permissions as arp', 'arp.role_id', '=', 'ar.id')
            ->join('auth_permissions as ap', 'ap.id', '=', 'arp.permission_id')
            ->where('aucr.user_company_access_id', $accessId)
            ->where('ar.is_active', true)
            ->where('ap.is_active', true)
            ->distinct()
            ->orderBy('ap.code')
            ->pluck('ap.code')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

        private function findPermissionItemsByAccessId(int $accessId): array
    {
        return DB::table('auth_user_company_roles as aucr')
            ->join('auth_roles as ar', 'ar.id', '=', 'aucr.role_id')
            ->join('auth_role_permissions as arp', 'arp.role_id', '=', 'ar.id')
            ->join('auth_permissions as ap', 'ap.id', '=', 'arp.permission_id')
            ->where('aucr.user_company_access_id', $accessId)
            ->where('ar.is_active', true)
            ->where('ap.is_active', true)
            ->distinct()
            ->orderBy('ap.code')
            ->get([
                'ap.id',
                'ap.code',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
            ])
            ->all();
    }
}
