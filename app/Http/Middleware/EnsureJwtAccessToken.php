<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureJwtAccessToken
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
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

            if (($payload['stage'] ?? null) !== 'access') {
                return response()->json([
                    'message' => 'El token no corresponde a una sesión de acceso.',
                ], 401);
            }

            $userId = (int) ($payload['uid'] ?? 0);
            $companyId = (int) ($payload['company_id'] ?? 0);

            $user = $this->authUserRepository->findById($userId);

            if (!$user || !$user->isActive()) {
                return response()->json([
                    'message' => 'El usuario del token no es válido o está inactivo.',
                ], 401);
            }

            $company = $this->companyStateRepository->findById($companyId);

            if (!$company || !$company->isActive()) {
                return response()->json([
                    'message' => 'La empresa del token no es válida o está inactiva.',
                ], 401);
            }

            $access = $this->userCompanyAccessRepository->findAccessibleCompanyByUserAndCompany(
                $userId,
                $companyId
            );

            if (!$access) {
                return response()->json([
                    'message' => 'El usuario no tiene acceso activo a la empresa del token.',
                ], 403);
            }

            $request->attributes->set('auth_payload', $payload);
            $request->attributes->set('auth_user_id', $userId);
            $request->attributes->set('auth_user_email', $user->email());
            $request->attributes->set('auth_company_id', $companyId);
            $request->attributes->set('auth_roles', $access->roleCodes());
            $request->attributes->set('auth_permissions', $access->permissionCodes());

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token inválido.',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
