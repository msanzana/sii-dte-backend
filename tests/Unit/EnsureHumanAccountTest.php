<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureHumanAccount;
use App\Modules\Auth\Domain\Entities\AuthUser;
use Illuminate\Http\Request;
use Tests\TestCase;

final class EnsureHumanAccountTest extends TestCase
{
    public function test_permite_contexto_humano_sin_sistema_externo(): void
    {
        $request = Request::create('/internal/auth/me', 'GET');
        $request->attributes->set(
            'auth_account_type',
            AuthUser::ACCOUNT_TYPE_HUMAN
        );
        $request->attributes->set('auth_external_system_id', null);

        $response = (new EnsureHumanAccount())->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rechaza_contexto_tecnico_en_ruta_humana(): void
    {
        $request = Request::create('/internal/auth/admin/users', 'GET');
        $request->attributes->set(
            'auth_account_type',
            AuthUser::ACCOUNT_TYPE_TECHNICAL
        );
        $request->attributes->set('auth_external_system_id', 7);

        $response = (new EnsureHumanAccount())->handle(
            $request,
            static fn () => response()->json(['ok' => true])
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
