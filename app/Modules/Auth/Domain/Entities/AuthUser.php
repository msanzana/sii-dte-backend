<?php
namespace App\Modules\Auth\Domain\Entities;
final class AuthUser
{
    public function __construct(
        private readonly int  $id,
        private readonly string $fullName,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly bool $isActive,
        private readonly string $lastLoginAt,
    ) {
    }

    public function id():int
    {
        return $this->id;
    }

    public function fullName():string
    {
        return $this->fullName;
    }

    public function email():string
    {
        return $this->email;
    }

    public function passwordHash():string
    {
        return $this->passwordHash;
    }

    public function isActive():bool
    {
        return $this->isActive;
    }

    public function lastLoginAt():string
    {
        return $this->lastLoginAt;
    }

}
