<?php
namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthUser;

interface AuthUserRepositoryInterface
{
    public function findActiveByEmail(string $email): ?AuthUser;
    public function findById(int $id): ?AuthUser;
    public function touchLastLoginAt(int $userId): void;

}

