<?php

namespace App\Modules\Auth\Application\DTOs;

final class CreateAuthRoleInputDto
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isSystem,
        public readonly bool $isActive,
    ) {
    }
}
