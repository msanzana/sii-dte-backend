<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\UserCompanyAccess;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use Illuminate\Support\Facades\DB;


final class  EloquentUserCompanyAccessRepository implements UserCompanyAccessRepositoryInterface
{


    public function findAccessibleCompaniesByUserId(int $userId): array
    {
        $rows = DB::table('auth_user_company_accesses as auca')
            ->join('companies as c', 'c.id', '=', 'auca.company_id')
            ->where('auca.user_id', $userId)
            ->where('auca.is_active', true)
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

        $result = [];

        foreach ($rows as $row) {
            $roles = $this->findRoleCodesByAccessId((int) $row->access_id);
            $permissions = $this->findPermissionCodesByAccessId((int) $row->access_id);

            $result[] = new UserCompanyAccess(
                accessId: (int) $row->access_id,
                userId: (int) $row->user_id,
                companyId: (int) $row->company_id,
                companyRut: (string) $row->company_rut,
                companyName: (string) $row->company_name,
                isActive: (bool) $row->is_active,
                isDefault: (bool) $row->is_default,
                canSelectCompany: (bool) $row->can_select_company,
                roleCodes: $roles,
                permissionCodes: $permissions,
            );
        }

        return $result;
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

        if (!$row) {
            return null;
        }

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

}
