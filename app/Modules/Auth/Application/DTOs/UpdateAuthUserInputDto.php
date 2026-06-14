<?php

namespace App\Modules\Auth\Application\DTOs;

final class UpdateAuthUserInputDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $fullName,
        public readonly string $email,
        public readonly ?string $password,
    ) {
    }
}
