<?php

namespace App\Modules\Auth\Application\DTOs;

final class ResetPasswordInputDto
{
    public function __construct(
        public readonly string $resetToken,
        public readonly string $password,
    ) {
    }
}
