<?php

namespace App\Modules\Auth\Application\DTOs;

final class UpdateAuthPermissionInputDto
{
    public function __construct(
        public readonly int $permissionId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $module,
        public readonly ?string $description,
    ) {
    }
}
