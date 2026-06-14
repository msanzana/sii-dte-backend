<?php

namespace App\Modules\Auth\Application\DTOs;

final class CreateAuthPermissionInputDto
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $module,
        public readonly ?string $description,
        public readonly bool $isActive,
    ) {
    }
}
