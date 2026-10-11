<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Application\DTOs\TechnicalLoginInputDto;
use App\Modules\Auth\Application\DTOs\TechnicalRefreshAccessTokenInputDto;
use App\Modules\Auth\Application\UseCases\TechnicalLoginUseCase;
use App\Modules\Auth\Application\UseCases\TechnicalRefreshAccessTokenUseCase;
use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\Exception\InvalidCredentialsException;
use App\Modules\Auth\Domain\Exception\InvalidRefreshTokenException;
use App\Modules\Auth\Presentation\Http\Requests\TechnicalLoginRequest;
use App\Modules\Auth\Presentation\Http\Requests\TechnicalRefreshTokenRequest;
use Illuminate\Http\JsonResponse;

final class TechnicalAuthController extends Controller
{
    public function __construct(
        private readonly TechnicalLoginUseCase $technicalLoginUseCase,
        private readonly TechnicalRefreshAccessTokenUseCase $technicalRefreshAccessTokenUseCase,
    ) {
    }

    public function login(TechnicalLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->technicalLoginUseCase->execute(
                new TechnicalLoginInputDto(
                    loginUuid: (string) $request->validated('login_uuid'),
                    password: (string) $request->validated('password'),
                    userAgent: $request->userAgent(),
                    ipAddress: $request->ip(),
                )
            );

            return response()->json([
                'message' => 'Autenticación técnica correcta.',
                'data' => [
                    'user_id' => $result->userId,
                    'login_uuid' => $result->loginUuid,
                    'account_type' => AuthUser::ACCOUNT_TYPE_TECHNICAL,
                    'company_id' => $result->companyId,
                    'company_rut' => $result->companyRut,
                    'company_name' => $result->companyName,
                    'external_system_id' => $result->externalSystemId,
                    'external_system_code' => $result->externalSystemCode,
                    'external_system_name' => $result->externalSystemName,
                    'access_token' => $result->accessToken,
                    'access_expires_in_seconds' => $result->accessExpiresInSeconds,
                    'refresh_token' => $result->refreshToken,
                    'refresh_expires_in_seconds' => $result->refreshExpiresInSeconds,
                    'token_stage' => 'access',
                ],
            ], 200);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'message' => 'No fue posible autenticar la integración.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function refresh(TechnicalRefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->technicalRefreshAccessTokenUseCase->execute(
                new TechnicalRefreshAccessTokenInputDto(
                    refreshToken: (string) $request->validated('refresh_token'),
                    userAgent: $request->userAgent(),
                    ipAddress: $request->ip(),
                )
            );

            return response()->json([
                'message' => 'Sesión técnica renovada correctamente.',
                'data' => [
                    'user_id' => $result->userId,
                    'account_type' => AuthUser::ACCOUNT_TYPE_TECHNICAL,
                    'company_id' => $result->companyId,
                    'company_rut' => $result->companyRut,
                    'company_name' => $result->companyName,
                    'external_system_id' => $result->externalSystemId,
                    'external_system_code' => $result->externalSystemCode,
                    'external_system_name' => $result->externalSystemName,
                    'access_token' => $result->accessToken,
                    'access_expires_in_seconds' => $result->accessExpiresInSeconds,
                    'refresh_token' => $result->refreshToken,
                    'refresh_expires_in_seconds' => $result->refreshExpiresInSeconds,
                    'token_stage' => 'access',
                ],
            ], 200);
        } catch (InvalidRefreshTokenException $e) {
            return response()->json([
                'message' => 'No fue posible renovar la sesión técnica.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
