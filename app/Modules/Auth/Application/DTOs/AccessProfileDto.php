<?php

namespace App\Modules\Auth\Application\DTOs;

final class AccessProfileDto
{
    public function __construct(
        public readonly array $roles,
        public readonly array $permissions,
        public readonly array $modules,
        public readonly array $menu,
        public readonly array $actions,
    ){}

    public function toArray():array
    {
        return [
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'modules' => $this->modules,
            'menu' => $this->menu,
            'actions' => $this->actions,
        ];
    }
}

