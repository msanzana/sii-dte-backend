<?php
namespace App\Modules\Auth\Application\DTOs;

final class SelectCompanyResultDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $fullName,
        public readonly string $email,
        public readonly int $companyId,
        public readonly string $companyRut,
        public readonly string $companyName,
        public readonly string $token,
        public readonly int $expiresInSeconds,
        public readonly array $roles,
        public readonly array $permissions,
    ){}
}
