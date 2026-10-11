<?php

namespace Tests\Feature;
use App\Http\Middleware\EnsureHumanAccount;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Tests\TestCase;
final class TechnicalJwtRouteIsolationTest extends TestCase
{
    public function test_alias_jwt_human_esta_registrado(): void
    {
        // Fuerza la resolución del HTTP Kernel.
        // Laravel aplica aquí los aliases declarados mediante withMiddleware().
        app(HttpKernel::class);

        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey(
            'jwt.human',
            $aliases
        );

        $this->assertSame(
            EnsureHumanAccount::class,
            $aliases['jwt.human']
        );
    }

    public function test_rutas_auth_protegidas_exigen_jwt_access_y_jwt_human(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(
                static fn ($route) => $route->uri() === 'api/internal/auth/me'
            );

        $this->assertNotNull($route);

        $middleware = $route->middleware();

        $this->assertContains('jwt.access', $middleware);
        $this->assertContains('jwt.human', $middleware);
    }

    public function test_rutas_dte_exigen_access_pero_no_human_para_permitir_integraciones_tecnicas(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(
                static fn ($route) => $route->uri() === 'api/internal/dte/document'
            );

        $this->assertNotNull($route);

        $middleware = $route->middleware();

        $this->assertContains('jwt.access', $middleware);
        $this->assertNotContains('jwt.human', $middleware);
    }
}
