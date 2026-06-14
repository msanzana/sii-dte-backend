<?php

namespace App\Modules\Auth\Application\DTOs;

final class UpdateAuthRoleInputDto
{
    public function __construct(
        public readonly int $roleId,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
    ) {
    }
}
