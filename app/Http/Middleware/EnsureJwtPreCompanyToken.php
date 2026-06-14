<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureJwtPreCompanyToken
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly AuthUserRepositoryInterface $authUserRepository,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $token = (string) $request->bearerToken();

            if (trim($token) === '') {
                return response()->json([
                    'message' => 'No existe bearer token.',
                ], 401);
            }

            $payload = $this->jwtHs256Service->parseAndValidate($token);

            if (($payload['stage'] ?? null) !== 'pre_company') {
                return response()->json([
                    'message' => 'El token no corresponde a la etapa pre_company.',
                ], 401);
            }

            $userId = (int) ($payload['uid'] ?? 0);
            $user = $this->authUserRepository->findById($userId);

            if (!$user || !$user->isActive()) {
                return response()->json([
                    'message' => 'El usuario del token no es válido o está inactivo.',
                ], 401);
            }

            $request->attributes->set('auth_payload', $payload);
            $request->attributes->set('auth_user_id', $userId);
            $request->attributes->set('auth_user_email', $user->email());

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token inválido.',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
