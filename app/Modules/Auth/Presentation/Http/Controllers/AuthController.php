<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\CurrentSessionInputDto;
use App\Modules\Auth\Application\DTOs\ListAccessibleCompaniesInputDto;
use App\Modules\Auth\Application\DTOs\PasswordLoginInputDto;
use App\Modules\Auth\Application\DTOs\RefreshAccessTokenInputDto;
use App\Modules\Auth\Application\DTOs\ResetPasswordInputDto;
use App\Modules\Auth\Application\DTOs\SelectCompanyInputDto;
use App\Modules\Auth\Application\DTOs\SendPasswordResetLinkInputDto;
use App\Modules\Auth\Application\UseCases\GetCurrentSessionUseCase;
use App\Modules\Auth\Application\UseCases\ListAccessibleCompaniesUseCase;
use App\Modules\Auth\Application\UseCases\PasswordLoginUseCase;
use App\Modules\Auth\Application\UseCases\RefreshAccessTokenUseCase;
use App\Modules\Auth\Application\UseCases\ResetPasswordUseCase;
use App\Modules\Auth\Application\UseCases\SelectCompanyUseCase;
use App\Modules\Auth\Application\UseCases\SendPasswordResetLinkUseCase;
use App\Modules\Auth\Domain\Exception\CompanySelectionNotAllowedException;
use App\Modules\Auth\Domain\Exception\InvalidCredentialsException;
use App\Modules\Auth\Domain\Exception\InvalidJwtTokenException;
use App\Modules\Auth\Domain\Exceptions\InvalidRefreshTokenException;
use App\Modules\Auth\Domain\Exceptions\PasswordResetException;
use App\Modules\Auth\Infrastructure\Presentation\Http\Requests\PasswordLoginRequest;
use App\Modules\Auth\Infrastructure\Presentation\Http\Requests\SelectCompanyRequest;
use App\Modules\Auth\Presentation\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Presentation\Http\Requests\RefreshTokenRequest;
use App\Modules\Auth\Presentation\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly PasswordLoginUseCase $passwordLoginUseCase,
        private readonly ListAccessibleCompaniesUseCase $listAccessibleCompaniesUseCase,
        private readonly SelectCompanyUseCase $selectCompanyUseCase,
        private readonly GetCurrentSessionUseCase $getCurrentSessionUseCase,
        private readonly RefreshAccessTokenUseCase $refreshAccessTokenUseCase,
        private readonly SendPasswordResetLinkUseCase $sendPasswordResetLinkUseCase,
        private readonly ResetPasswordUseCase $resetPasswordUseCase,
    ) {
    }

    public function login(PasswordLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->passwordLoginUseCase->execute(
                new PasswordLoginInputDto(
                    email: (string) $request->validated('email'),
                    password: (string) $request->validated('password'),
                )
            );

            return response()->json([
                'message' => 'Autenticación inicial correcta.',
                'data' => [
                    'user_id' => $result->userId,
                    'full_name' => $result->fullName,
                    'email' => $result->email,
                    'token' => $result->token,
                    'token_stage' => $result->tokenStage,
                    'expires_in_seconds' => $result->expiresInSeconds,
                    'companies_count' => $result->companiesCount,
                ],
            ], 200);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'message' => 'No fue posible autenticar al usuario.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function companies(): JsonResponse
    {
        try {
            $result = $this->listAccessibleCompaniesUseCase->execute(
                new ListAccessibleCompaniesInputDto(
                    bearerToken: (string) request()->bearerToken()
                )
            );

            return response()->json([
                'message' => 'Empresas del usuario obtenidas correctamente.',
                'data' => [
                    'user_id' => $result->userId,
                    'companies' => $result->companies,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No fue posible obtener las empresas del usuario.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function selectCompany(SelectCompanyRequest $request): JsonResponse
    {
        try {
            $result = $this->selectCompanyUseCase->execute(
                new SelectCompanyInputDto(
                    bearerToken: (string) request()->bearerToken(),
                    companyId: (int) $request->validated('company_id'),
                    userAgent: request()->userAgent(),
                    ipAddress: request()->ip(),
                )
            );

            return response()->json([
                'message' => 'Empresa seleccionada correctamente.',
                'data' => [
                    'user_id' => $result->userId,
                    'full_name' => $result->fullName,
                    'email' => $result->email,
                    'company_id' => $result->companyId,
                    'company_rut' => $result->companyRut,
                    'company_name' => $result->companyName,
                    'access_token' => $result->accessToken,
                    'access_expires_in_seconds' => $result->accessExpiresInSeconds,
                    'refresh_token' => $result->refreshToken,
                    'refresh_expires_in_seconds' => $result->refreshExpiresInSeconds,
                    'token_stage' => 'access',
                    'access_profile' => $result->accessProfile->toArray(),
                ],
            ], 200);
        } catch (InvalidJwtTokenException|CompanySelectionNotAllowedException $e) {
            return response()->json([
                'message' => 'No fue posible seleccionar la empresa.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function me(): JsonResponse
    {
        try {
            $result = $this->getCurrentSessionUseCase->execute(
                new CurrentSessionInputDto(
                    bearerToken: (string) request()->bearerToken()
                )
            );

            return response()->json([
                'message' => 'Sesión actual obtenida correctamente.',
                'data' => [
                    'user_id' => $result->userId,
                    'full_name' => $result->fullName,
                    'email' => $result->email,
                    'company_id' => $result->companyId,
                    'company_rut' => $result->companyRut,
                    'company_name' => $result->companyName,
                    'access_profile' => $result->accessProfile->toArray(),
                ],
            ], 200);
        } catch (InvalidJwtTokenException $e) {
            return response()->json([
                'message' => 'No fue posible obtener la sesión actual.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->refreshAccessTokenUseCase->execute(
                new RefreshAccessTokenInputDto(
                    refreshToken: (string) $request->validated('refresh_token'),
                    userAgent: request()->userAgent(),
                    ipAddress: request()->ip(),
                )
            );

            return response()->json([
                'message' => 'Sesión renovada correctamente.',
                'data' => [
                    'user_id' => $result->userId,
                    'company_id' => $result->companyId,
                    'company_rut' => $result->companyRut,
                    'company_name' => $result->companyName,
                    'access_token' => $result->accessToken,
                    'access_expires_in_seconds' => $result->accessExpiresInSeconds,
                    'refresh_token' => $result->refreshToken,
                    'refresh_expires_in_seconds' => $result->refreshExpiresInSeconds,
                    'access_profile' => $result->accessProfile->toArray(),
                ],
            ], 200);
        } catch (InvalidRefreshTokenException $e) {
            return response()->json([
                'message' => 'No fue posible renovar la sesión.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->sendPasswordResetLinkUseCase->execute(
            new SendPasswordResetLinkInputDto(
                email: (string) $request->validated('email'),
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            )
        );

        return response()->json([
            'message' => 'Si el usuario existe y está activo, se enviará un correo de recuperación.',
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->resetPasswordUseCase->execute(
                new ResetPasswordInputDto(
                    resetToken: (string) $request->validated('reset_token'),
                    password: (string) $request->validated('password'),
                )
            );

            return response()->json([
                'message' => 'Contraseña restablecida correctamente.',
            ], 200);
        } catch (PasswordResetException $e) {
            return response()->json([
                'message' => 'No fue posible restablecer la contraseña.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
