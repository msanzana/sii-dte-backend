<?php

namespace App\Modules\Auth\Domain\RepositoryContracts;

use App\Modules\Auth\Domain\Entities\AuthUser;

interface AuthUserRepositoryInterface
{
    public function findById(int $id): ?AuthUser;

    public function findByEmail(string $email): ?AuthUser;

    public function findActiveByEmail(string $email): ?AuthUser;

    public function touchLastLoginAt(int $userId): void;

    /**
     * @return AuthUser[]
     */
    public function findAll(?bool $isActive = null, int $limit = 100): array;

    public function create(
        string $fullName,
        string $email,
        string $passwordHash,
        bool $isActive = true
    ): AuthUser;

    public function update(
        int $userId,
        string $fullName,
        string $email
    ): AuthUser;

    public function setPassword(int $userId, string $passwordHash): void;

    public function setActive(int $userId, bool $isActive): void;
}
