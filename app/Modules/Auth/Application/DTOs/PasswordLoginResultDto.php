<?php
namespace App\Modules\Auth\Application\DTOs;

final class PasswordLoginResultDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $token,
        public readonly string $tokenStage,
        public readonly int $expiresInSeconds,
        public readonly int $companiesCount,
    ){}
}
