<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Domain\Entities\AuthUser;
use Closure;
use Illuminate\Http\Request;

final class EnsureHumanAccount
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (
            $request->attributes->get('auth_account_type')
            !== AuthUser::ACCOUNT_TYPE_HUMAN
        ) {
            return response()->json([
                'message' => 'Esta ruta está disponible únicamente para cuentas humanas.',
            ], 403);
        }

        if ($request->attributes->get('auth_external_system_id') !== null) {
            return response()->json([
                'message' => 'Una sesión humana no puede estar asociada a un sistema externo.',
            ], 403);
        }

        return $next($request);
    }
}
