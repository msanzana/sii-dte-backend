<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureJwtPermission
{
    public function handle(Request $request, Closure $next, string ...$requiredPermissions): mixed
    {
        $permissions = $request->attributes->get('auth_permissions', []);

        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $permissions, true)) {
                return response()->json([
                    'message' => 'No tienes permisos suficientes para ejecutar esta acción.',
                    'required_permission' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}
