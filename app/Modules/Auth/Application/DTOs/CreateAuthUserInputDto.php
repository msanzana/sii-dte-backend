<?php

namespace App\Modules\Auth\Application\DTOs;

final class CreateAuthUserInputDto
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $password,
        public readonly bool $isActive,
    ) {
    }
}
