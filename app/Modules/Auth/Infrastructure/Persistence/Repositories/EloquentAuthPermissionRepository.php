<?php

namespace App\Modules\Auth\Application\Services;

use App\Modules\Auth\Application\DTOs\CreateAuthUserInputDto;
use App\Modules\Auth\Application\DTOs\UpdateAuthUserInputDto;
use App\Modules\Auth\Domain\Exceptions\AuthAdministrationException;
use App\Modules\Auth\Domain\Exceptions\AuthEntityNotFoundException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class ManageAuthUsersService
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $authUserRepository,
    ) {
    }

    public function list(?bool $isActive = null, int $limit = 100): array
    {
        return array_map(
            fn ($user) => [
                'id' => $user->id(),
                'full_name' => $user->fullName(),
                'email' => $user->email(),
                'is_active' => $user->isActive(),
                'last_login_at' => $user->lastLoginAt(),
            ],
            $this->authUserRepository->findAll($isActive, $limit)
        );
    }

    public function show(int $userId): array
    {
        $user = $this->authUserRepository->findById($userId);

        if (!$user) {
            throw AuthEntityNotFoundException::for('usuario', $userId);
        }

        return [
            'id' => $user->id(),
            'full_name' => $user->fullName(),
            'email' => $user->email(),
            'is_active' => $user->isActive(),
            'last_login_at' => $user->lastLoginAt(),
        ];
    }

    public function create(CreateAuthUserInputDto $input): array
    {
        if ($this->authUserRepository->findActiveByEmail($input->email)) {
            throw AuthAdministrationException::because(
                'Ya existe un usuario activo con ese email.'
            );
        }

        $user = $this->authUserRepository->create(
            fullName: $input->fullName,
            email: $input->email,
            passwordHash: Hash::make($input->password),
            isActive: $input->isActive
        );

        return $this->show($user->id());
    }

    public function update(UpdateAuthUserInputDto $input): array
    {
        $existing = $this->authUserRepository->findById($input->userId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('usuario', $input->userId);
        }

        $user = $this->authUserRepository->update(
            userId: $input->userId,
            fullName: $input->fullName,
            email: $input->email
        );

        if ($input->password !== null && trim($input->password) !== '') {
            $this->authUserRepository->setPassword(
                $input->userId,
                Hash::make($input->password)
            );
        }

        return $this->show($user->id());
    }

    public function setActive(int $userId, bool $isActive): array
    {
        $existing = $this->authUserRepository->findById($userId);

        if (!$existing) {
            throw AuthEntityNotFoundException::for('usuario', $userId);
        }

        $this->authUserRepository->setActive($userId, $isActive);

        return $this->show($userId);
    }
}
