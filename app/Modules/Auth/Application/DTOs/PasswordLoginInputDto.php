<?php
namespace App\Modules\Auth\Application\DTOs;

final class PasswordLoginInputDto
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {
    }
}
