<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureDteTechnicalScope;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Tests\TestCase;

final class DteTechnicalScopeRouteIsolationTest extends TestCase
{
    public function test_alias_jwt_dte_scope_esta_registrado(): void
    {
        app(HttpKernel::class);

        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('jwt.dte.scope', $aliases);
        $this->assertSame(
            EnsureDteTechnicalScope::class,
            $aliases['jwt.dte.scope']
        );
    }

    public function test_documento_dte_permanece_disponible_para_integracion_tecnica_pero_con_scope(): void
    {
        $middleware = $this->middlewareFor('api/internal/dte/document');

        $this->assertContains('jwt.access', $middleware);
        $this->assertContains('jwt.dte.scope', $middleware);
        $this->assertNotContains('jwt.human', $middleware);
    }

    public function test_operaciones_por_document_id_tambien_exigen_scope_tecnico(): void
    {
        $middleware = $this->middlewareFor(
            'api/internal/dte/documents/{documentId}/build-xml'
        );

        $this->assertContains('jwt.access', $middleware);
        $this->assertContains('jwt.dte.scope', $middleware);
        $this->assertNotContains('jwt.human', $middleware);
    }

    public function test_administracion_de_certificados_y_caf_es_solo_humana(): void
    {
        foreach ([
            'api/internal/dte/certificates',
            'api/internal/dte/cafs',
            'api/internal/dte/cafs/{cafId}/range-allocations',
        ] as $uri) {
            $middleware = $this->middlewareFor($uri);

            $this->assertContains('jwt.access', $middleware);
            $this->assertContains('jwt.dte.scope', $middleware);
            $this->assertContains('jwt.human', $middleware);
        }
    }

    public function test_administracion_global_dte_es_solo_humana(): void
    {
        foreach ([
            'api/internal/dte/service',
            'api/internal/dte/companies',
            'api/internal/dte/external-systems',
            'api/internal/dte/notices',
        ] as $uri) {
            $middleware = $this->middlewareFor($uri);

            $this->assertContains('jwt.human', $middleware);
        }
    }

    public function test_folios_y_reservas_siguen_disponibles_para_integracion_tecnica_con_scope(): void
    {
        foreach ([
            'api/internal/dte/folios/available',
            'api/internal/dte/folios/reserve',
            'api/internal/dte/folios/release',
            'api/internal/dte/folio-reservations',
            'api/internal/dte/folio-reservations/{reservationId}',
        ] as $uri) {
            $middleware = $this->middlewareFor($uri);

            $this->assertContains('jwt.dte.scope', $middleware);
            $this->assertNotContains('jwt.human', $middleware);
        }
    }

    private function middlewareFor(string $uri): array
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(
                static fn ($route) => $route->uri() === $uri
            );

        $this->assertNotNull(
            $route,
            "No se encontró la ruta {$uri}."
        );

        return $route->middleware();
    }
}
