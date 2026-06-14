<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\SendPasswordResetLinkInputDto;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPasswordResetTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\PasswordResetTokenService;
use App\Modules\Auth\Presentation\Notifications\AuthPasswordResetNotification;
use Illuminate\Support\Facades\Notification;

final class SendPasswordResetLinkUseCase
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthPasswordResetTokenRepositoryInterface $authPasswordResetTokenRepository,
        private readonly PasswordResetTokenService $passwordResetTokenService,
    ) {
    }

    public function execute(SendPasswordResetLinkInputDto $input): void
    {
        $user = $this->authUserRepository->findActiveByEmail($input->email);

        if (!$user) {
            return;
        }

        $this->authPasswordResetTokenRepository->invalidateAllByUser($user->id());

        $ttl = (int) config('platform_auth.password_reset.ttl_seconds', 3600);
        $secret = $this->passwordResetTokenService->generateSecret();

        $record = $this->authPasswordResetTokenRepository->create(
            userId: $user->id(),
            tokenHash: $this->passwordResetTokenService->hashSecret($secret),
            expiresAt: now()->addSeconds($ttl)->format('Y-m-d H:i:s'),
            ipAddress: $input->ipAddress,
            userAgent: $input->userAgent
        );

        $publicToken = $this->passwordResetTokenService->buildPublicToken(
            $record->id(),
            $secret
        );

        $baseUrl = rtrim((string) config('platform_auth.password_reset.frontend_url'), '/');
        $resetUrl = $baseUrl . '?token=' . urlencode($publicToken);

        Notification::route('mail', $user->email())
            ->notify(new AuthPasswordResetNotification($resetUrl, $ttl));
    }
}
