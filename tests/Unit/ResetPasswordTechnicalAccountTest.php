<?php

namespace Tests\Unit;

use App\Modules\Auth\Application\DTOs\ResetPasswordInputDto;
use App\Modules\Auth\Application\UseCases\ResetPasswordUseCase;
use App\Modules\Auth\Domain\Entities\AuthPasswordResetToken;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Exception\PasswordResetException;
use App\Modules\Auth\Domain\RepositoryContracts\AuthPasswordResetTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthRefreshTokenRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\PasswordResetTokenService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

final class ResetPasswordTechnicalAccountTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_token_valido_de_usuario_tecnico_no_permite_resetear_password(): void
    {
        $tokenService = new PasswordResetTokenService();
        $secret = str_repeat('a', 64);

        $tokens = $this->tokensReturningValidToken($tokenService, $secret, userId: 10);
        $tokens->shouldNotReceive('markUsed');
        $tokens->shouldNotReceive('invalidateAllByUser');

        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $refreshTokens->shouldNotReceive('revokeAllByUser');

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findById')->once()->with(10)->andReturn($this->user(10, technical: true));
        $users->shouldNotReceive('setPassword');

        $useCase = new ResetPasswordUseCase(
            authPasswordResetTokenRepository: $tokens,
            authRefreshTokenRepository: $refreshTokens,
            authUserRepository: $users,
            passwordResetTokenService: $tokenService,
        );

        $this->expectException(PasswordResetException::class);
        $this->expectExceptionMessage('El usuario asociado al token no es válido o está inactivo.');

        $useCase->execute(new ResetPasswordInputDto(
            resetToken: $tokenService->buildPublicToken(5, $secret),
            password: 'NuevaPassword123!',
        ));
    }

    public function test_usuario_humano_activo_puede_resetear_password(): void
    {
        $tokenService = new PasswordResetTokenService();
        $secret = str_repeat('b', 64);

        $tokens = $this->tokensReturningValidToken($tokenService, $secret, userId: 1);
        $tokens->shouldReceive('markUsed')->once()->with(5);
        $tokens->shouldReceive('invalidateAllByUser')->once()->with(1);

        $refreshTokens = Mockery::mock(AuthRefreshTokenRepositoryInterface::class);
        $refreshTokens->shouldReceive('revokeAllByUser')->once()->with(1);

        $users = Mockery::mock(AuthUserRepositoryInterface::class);
        $users->shouldReceive('findById')->once()->with(1)->andReturn($this->user(1, technical: false));
        $writtenHash = null;
        $users
            ->shouldReceive('setPassword')
            ->once()
            ->with(1, Mockery::on(function ($hash) use (&$writtenHash): bool {
                if (!is_string($hash)) {
                    return false;
                }

                $writtenHash = $hash;
                return true;
            }));

        $useCase = new ResetPasswordUseCase(
            authPasswordResetTokenRepository: $tokens,
            authRefreshTokenRepository: $refreshTokens,
            authUserRepository: $users,
            passwordResetTokenService: $tokenService,
        );

        $useCase->execute(new ResetPasswordInputDto(
            resetToken: $tokenService->buildPublicToken(5, $secret),
            password: 'NuevaPassword123!',
        ));

        $this->assertNotNull($writtenHash);
        $this->assertTrue(Hash::check('NuevaPassword123!', $writtenHash));
    }

    private function tokensReturningValidToken(
        PasswordResetTokenService $tokenService,
        string $secret,
        int $userId
    ): AuthPasswordResetTokenRepositoryInterface {
        $tokens = Mockery::mock(AuthPasswordResetTokenRepositoryInterface::class);
        $tokens
            ->shouldReceive('findById')
            ->once()
            ->with(5)
            ->andReturn(new AuthPasswordResetToken(
                id: 5,
                userId: $userId,
                tokenHash: $tokenService->hashSecret($secret),
                expiresAt: now()->addHour()->format('Y-m-d H:i:s'),
                usedAt: null,
                ipAddress: null,
                userAgent: null,
            ));

        return $tokens;
    }

    private function user(int $id, bool $technical): AuthUser
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        return new AuthUser(
            id: $id,
            fullName: $technical ? $uuid : 'Usuario Humano',
            email: $technical ? $uuid . '@technical.invalid' : 'humano@example.com',
            accountType: $technical ? AuthUser::ACCOUNT_TYPE_TECHNICAL : AuthUser::ACCOUNT_TYPE_HUMAN,
            loginUuid: $technical ? $uuid : null,
            passwordHash: 'hash-anterior',
            isActive: true,
            isVisible: !$technical,
            lastLoginAt: null,
        );
    }
}
