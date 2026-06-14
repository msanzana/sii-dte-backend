<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\ResetPasswordInputDto;
use App\Modules\Auth\Domain\Exceptions\PasswordResetException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPasswordResetTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\PasswordResetTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class ResetPasswordUseCase
{
    public function __construct(
        private readonly AuthPasswordResetTokenRepositoryInterface $authPasswordResetTokenRepository,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly PasswordResetTokenService $passwordResetTokenService,
    ) {
    }

    public function execute(ResetPasswordInputDto $input): void
    {
        DB::transaction(function () use ($input) {
            $parsed = $this->passwordResetTokenService->parsePublicToken($input->resetToken);

            $token = $this->authPasswordResetTokenRepository->findById($parsed['id']);

            if (!$token) {
                throw PasswordResetException::because(
                    'El token de recuperación no existe.'
                );
            }

            if (!hash_equals($token->tokenHash(), $parsed['hash'])) {
                throw PasswordResetException::because(
                    'El token de recuperación no es válido.'
                );
            }

            if ($token->isUsed()) {
                throw PasswordResetException::because(
                    'El token de recuperación ya fue utilizado.'
                );
            }

            if (now()->gte($token->expiresAt())) {
                throw PasswordResetException::because(
                    'El token de recuperación expiró.'
                );
            }

            $user = $this->authUserRepository->findById($token->userId());

            if (!$user || !$user->isActive()) {
                throw PasswordResetException::because(
                    'El usuario asociado al token no es válido o está inactivo.'
                );
            }

            $this->authUserRepository->setPassword(
                $user->id(),
                Hash::make($input->password)
            );

            $this->authPasswordResetTokenRepository->markUsed($token->id());
            $this->authPasswordResetTokenRepository->invalidateAllByUser($user->id());
            $this->authRefreshTokenRepository->revokeAllByUser($user->id());
        });
    }
}
