<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use App\Modules\Auth\Domain\RepositoryContracts\UserCompanyAccessRepositoryInterface;
use App\Modules\Auth\Infrastructure\Security\JwtHs256Service;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use Closure;
use Illuminate\Http\Request;

class EnsureJwtAccessToken
{
    public function __construct(
        private readonly JwtHs256Service $jwtHs256Service,
        private readonly AuthUserRepositoryInterface $authUserRepository,
        private readonly AuthCompanyStateRepositoryInterface $companyStateRepository,
        private readonly UserCompanyAccessRepositoryInterface $userCompanyAccessRepository,
        private readonly TechnicalUserSystemAccessRepositoryInterface $technicalUserSystemAccessRepository,
        private readonly ExternalSystemRepositoryInterface $externalSystemRepository,
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

            if ($userId <= 0 || $companyId <= 0) {
                return response()->json([
                    'message' => 'El token no contiene un ámbito de acceso válido.',
                ], 401);
            }

            $user = $this->authUserRepository->findById($userId);

            if (!$user || !$user->isActive()) {
                return response()->json([
                    'message' => 'El usuario del token no es válido o está inactivo.',
                ], 401);
            }

            $tokenAccountType = $payload['account_type'] ?? null;
            $externalSystemId = null;

            if ($user->isTechnical()) {
                if ($tokenAccountType !== AuthUser::ACCOUNT_TYPE_TECHNICAL) {
                    return response()->json([
                        'message' => 'El token no corresponde a una sesión técnica válida.',
                    ], 401);
                }

                $externalSystemId = (int) ($payload['external_system_id'] ?? 0);

                if ($externalSystemId <= 0) {
                    return response()->json([
                        'message' => 'El token técnico no contiene un sistema externo válido.',
                    ], 401);
                }
            } else {
                if (!$user->isHuman()) {
                    return response()->json([
                        'message' => 'El tipo de cuenta del usuario no es válido.',
                    ], 401);
                }

                // Compatibilidad con los JWT humanos existentes, que fueron emitidos
                // antes de incorporar el claim account_type.
                if (
                    $tokenAccountType !== null
                    && $tokenAccountType !== AuthUser::ACCOUNT_TYPE_HUMAN
                ) {
                    return response()->json([
                        'message' => 'El token no corresponde a una sesión humana válida.',
                    ], 401);
                }

                if (
                    array_key_exists('external_system_id', $payload)
                    && $payload['external_system_id'] !== null
                ) {
                    return response()->json([
                        'message' => 'Un token humano no puede declarar un sistema externo.',
                    ], 401);
                }
            }

            $company = $this->companyStateRepository->findById($companyId);

            if (!$company || !$company->isActive()) {
                return response()->json([
                    'message' => 'La empresa del token no es válida o está inactiva.',
                ], 401);
            }

            // Tanto humanos como técnicos deben conservar un acceso activo a la empresa.
            $companyAccess = $this->userCompanyAccessRepository
                ->findAccessibleCompanyByUserAndCompany($userId, $companyId);

            if (!$companyAccess) {
                return response()->json([
                    'message' => 'El usuario no tiene acceso activo a la empresa del token.',
                ], 403);
            }

            if ($user->isTechnical()) {
                $activeTechnicalAccesses = $this->technicalUserSystemAccessRepository
                    ->findActiveByUserId($userId);

                // Una credencial técnica representa exactamente un ámbito empresa+sistema.
                if (count($activeTechnicalAccesses) !== 1) {
                    return response()->json([
                        'message' => 'El ámbito técnico del usuario ya no es válido.',
                    ], 403);
                }

                $technicalAccess = $activeTechnicalAccesses[0];

                if (
                    $technicalAccess->companyId() !== $companyId
                    || $technicalAccess->externalSystemId() !== $externalSystemId
                ) {
                    return response()->json([
                        'message' => 'El ámbito técnico del token ya no coincide con el acceso vigente.',
                    ], 403);
                }

                $externalSystem = $this->externalSystemRepository
                    ->findByCompanyAndId($companyId, $externalSystemId);

                if (
                    $externalSystem === null
                    || !$externalSystem->isActive()
                    || $externalSystem->companyId() !== $companyId
                ) {
                    return response()->json([
                        'message' => 'El sistema externo del token no es válido o está inactivo.',
                    ], 403);
                }

                // Las cuentas técnicas no heredan roles/permisos humanos aunque exista
                // accidentalmente información de roles en su acceso de empresa.
                $roles = [];
                $permissions = [];
            } else {
                $roles = $companyAccess->roleCodes();
                $permissions = $companyAccess->permissionCodes();
            }

            $request->attributes->set('auth_payload', $payload);
            $request->attributes->set('auth_user_id', $userId);
            $request->attributes->set('auth_user_email', $user->email());
            $request->attributes->set('auth_account_type', $user->accountType());
            $request->attributes->set('auth_company_id', $companyId);
            $request->attributes->set('auth_external_system_id', $externalSystemId);
            $request->attributes->set('auth_roles', $roles);
            $request->attributes->set('auth_permissions', $permissions);

            return $next($request);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token inválido.',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
