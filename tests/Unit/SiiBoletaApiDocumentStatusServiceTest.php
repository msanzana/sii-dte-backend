<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiDocumentStatusService;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;
use App\Modules\Dte\Infrastructure\Sii\SiiRequestThrottleService;
use ReflectionClass;
use ReflectionNamedType;

final class SiiBoletaApiDocumentStatusServiceTest extends TestCase
{
    public function test_resuelve_la_configuracion_desde_la_seccion_boleta_del_ambiente_cert(): void
    {
        config()->set(
            'dte.sii.boleta.cert.document_status_url',
            'https://example.test/boleta/document-status'
        );

        config()->set(
            'dte.sii.cert.document_status_url',
            ''
        );

        $service = $this->makeService();

        $method = new ReflectionMethod(
            SiiBoletaApiDocumentStatusService::class,
            'resolveConfig'
        );

        $method->setAccessible(true);

        try {
            $value = $method->invoke(
                $service,
                'cert',
                'document_status_url'
            );
        } catch (\Throwable $exception) {
            $this->fail(
                'La configuración de boleta debe leerse desde '
                . 'dte.sii.boleta.cert.document_status_url. '
                . 'Error recibido: '
                . $exception->getMessage()
            );
        }

        $this->assertSame(
            'https://example.test/boleta/document-status',
            $value
        );
    }
    public function test_envia_el_token_en_cookie_segun_el_contrato_oficial_del_sii(): void
    {
        config()->set(
            'dte.sii.boleta.cert.document_status_url',
            'https://example.test/boleta/document-status'
        );
        Http::fake([
            'https://example.test/boleta/document-status/*' => Http::response(
                '{}',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $service = $this->makeService();

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            payload: [
                'rut_emisor' => '76123456',
                'dv_emisor' => '7',
                'tipo_dte' => 39,
                'folio' => 123,
                'rut_receptor' => '12345678',
                'dv_receptor' => '9',
                'monto_total' => 15990,
                'fecha_emision' => '2026-09-08',
            ]
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'Cookie',
                'TOKEN=TOKEN-DE-PRUEBA'
            );
        });
    }
    public function test_extrae_estado_y_glosa_desde_una_respuesta_json(): void
    {
        config()->set(
            'dte.sii.boleta.cert.document_status_url',
            'https://example.test/boleta/document-status'
        );

        Http::fake([
            'https://example.test/boleta/document-status/*' => Http::response(
                [
                    'resultado' => [
                        'estado' => 'ACEPTADO',
                        'glosa' => 'Documento aceptado por el SII',
                    ],
                ],
                200
            ),
        ]);

        $service = $this->makeService();

        $result = $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            payload: [
                'rut_emisor' => '76123456',
                'dv_emisor' => '7',
                'tipo_dte' => 39,
                'folio' => 123,
                'rut_receptor' => '12345678',
                'dv_receptor' => '9',
                'monto_total' => 15990,
                'fecha_emision' => '2026-09-08',
            ]
        );

        $this->assertSame(
            [
                'ACEPTADO',
                'Documento aceptado por el SII',
            ],
            [
                $result['status_code'],
                $result['status_message'],
            ]
        );
    }

    public function test_consulta_el_estado_de_boleta_mediante_get_y_la_ruta_oficial_del_sii(): void
    {
        config()->set(
            'dte.sii.boleta.cert.document_status_url',
            'https://apicert.sii.cl/recursos/v1/boleta.electronica'
        );

        Http::fake([
            '*' => Http::response(
                [
                    'codigo' => 'DOK',
                    'descripcion' => 'Documento recibido por el SII',
                ],
                200
            ),
        ]);

        $service = $this->makeService();

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            payload: [
                'rut_emisor' => '76123456',
                'dv_emisor' => '7',
                'tipo_dte' => 39,
                'folio' => 123,
                'rut_receptor' => '12345678',
                'dv_receptor' => '9',
                'monto_total' => 15990,
                'fecha_emision' => '2026-09-08',
            ]
        );

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_starts_with(
                    $request->url(),
                    'https://apicert.sii.cl/recursos/v1/boleta.electronica/76123456-7-39-123/estado'
                );
        });
    }
    public function test_envia_los_parametros_oficiales_de_consulta_de_boleta(): void
    {
        config()->set(
            'dte.sii.boleta.cert.document_status_url',
            'https://apicert.sii.cl/recursos/v1/boleta.electronica'
        );

        Http::fake([
            '*' => Http::response(
                [
                    'codigo' => 'DOK',
                    'descripcion' => 'Documento recibido por el SII',
                ],
                200
            ),
        ]);

        $service = $this->makeService();

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            payload: [
                'rut_emisor' => '76123456',
                'dv_emisor' => '7',
                'tipo_dte' => 39,
                'folio' => 123,
                'rut_receptor' => '12345678',
                'dv_receptor' => '9',
                'monto_total' => 15990,
                'fecha_emision' => '2026-09-08',
            ]
        );

        Http::assertSent(function ($request) {
            $url = parse_url($request->url());

            parse_str(
                $url['query'] ?? '',
                $query
            );

            return $request->method() === 'GET'
                && ($query['rut_receptor'] ?? null) === '12345678'
                && ($query['dv_receptor'] ?? null) === '9'
                && ($query['monto'] ?? null) === '15990'
                && ($query['fechaEmision'] ?? null) === '08-09-2026';
        });
    }
    public function test_aplica_throttle_antes_de_consultar_el_estado_del_documento(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiDocumentStatusService::class
        );

        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor
        );

        $constructorTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                $constructorTypes[] = $type->getName();
            }
        }

        $this->assertContains(
            SiiRequestThrottleService::class,
            $constructorTypes,
            'SiiBoletaApiDocumentStatusService debe recibir SiiRequestThrottleService.'
        );

        $method = $reflection->getMethod(
            'query'
        );

        $lines = file(
            $method->getFileName()
        );

        $this->assertIsArray(
            $lines
        );

        $methodSource = implode(
            '',
            array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            )
        );

        $throttleCall =
            '$this->requestThrottleService->wait($environment);';

        $this->assertSame(
            1,
            substr_count(
                $methodSource,
                $throttleCall
            ),
            'query() debe aplicar throttle exactamente una vez.'
        );

        $throttlePosition = strpos(
            $methodSource,
            $throttleCall
        );

        $requestPosition = strrpos(
            $methodSource,
            '$response = Http::withHeaders(['
        );

        $this->assertIsInt(
            $throttlePosition
        );

        $this->assertIsInt(
            $requestPosition
        );

        $this->assertLessThan(
            $requestPosition,
            $throttlePosition,
            'El throttle debe ejecutarse antes de consultar el estado del documento.'
        );
    }
    private function makeService(): SiiBoletaApiDocumentStatusService
    {
        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        return new SiiBoletaApiDocumentStatusService(
            app(SiiRequestThrottleService::class)
        );
    }
}
