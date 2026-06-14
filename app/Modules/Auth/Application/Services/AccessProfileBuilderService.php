<?php
namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\AccessProfileDto;

final class AccessProfileBuilderService
{
    public function build(array $roles, array $permissions): AccessProfileDto
    {
        $hasAuth = $this->hasPermissionPrefix($permissions, 'auth.');
        $hasDte = $this->hasPermissionPrefix($permissions,'dte.');
        $hasSupport = $this->hasPermissionPrefix($permissions, 'support.');
        $hasDashboard = true;

        $modules =[
            'dashboard' => $hasDashboard,
            'auth_admin' => $hasAuth,
            'dte' => $hasDte,
            'support' => $hasSupport,
        ];

        $menu =[
            [
                'key' => 'dashboard',
                'label' => 'DashBoard',
                'visible' => $hasDashboard,
            ],
            [
                'key' => 'dte',
                'label' => 'DTE',
                'visible' => $hasDte,
            ],
            [
                'key' => 'auth_admin',
                'label' => 'Seguridad',
                'visible' => $hasAuth,
            ],
            [
                'key' => 'support',
                'label' => 'Soporte',
                'visible' => $hasSupport,
            ],
        ];
        $actions = [
            'can_manage_users' => in_array('auth.users.create',$permissions,true)
                || in_array('auth.users.update', $permissions, true),
            'can_manage_roles' => in_array('auth.roles.create', $permissions, true)
                || in_array('auth.roles.update', $permissions, true),
            'can_manage_permissions' => in_array('auth.permissions.create', $permissions, true)
                || in_array('auth.permissions.update', $permissions, true),
            'can_assign_companies' => in_array('auth.assignments.create', $permissions,true)
                || in_array('auth.assignments.update', $permissions, true),
            'can_operate_dte' => $hasDte,
            'can_view_support' => $hasSupport,
        ];

        return new AccessProfileDto(
            roles: array_values($roles),
            permissions: array_values($permissions),
            modules: $modules,
            menu: $menu,
            actions: $actions
        );
    }

    private function hasPermissionPrefix(array $permissions, string $prefix):bool
    {
        foreach ($permissions as $permission)
        {
            if(str_starts_with($permission, $prefix))
            {
                return true;
            }
        }

        return false;
    }
}

