<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiSendStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiRequestThrottleService;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

final class SiiBoletaApiSendStatusServiceTest extends TestCase
{
    public function test_aplica_throttle_antes_de_consultar_el_estado_del_envio(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiSendStatusService::class
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
            'SiiBoletaApiSendStatusService debe recibir SiiRequestThrottleService.'
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

        $requestPosition = strpos(
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
            'El throttle debe ejecutarse antes de consultar el estado del envío.'
        );
    }
    public function test_query_usa_la_clave_send_status_url_de_la_configuracion(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_status_url',
            'https://example.test/recursos/v1/boleta.electronica.envio/'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'estado' => 'EPR',
                    'glosa' => 'Envio procesado',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiSendStatusService(
            app(
                SiiRequestThrottleService::class
            )
        );

        $result = $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            rutBody: '76123456',
            rutDv: '7',
            trackId: '1234567890'
        );

        $this->assertSame(
            'EPR',
            $result['status_code']
        );

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://example.test/recursos/v1/boleta.electronica.envio/76123456-7-1234567890';
        });
    }
    public function test_query_envia_el_token_en_cookie(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_status_url',
            'https://example.test/recursos/v1/boleta.electronica.envio/'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'estado' => 'EPR',
                    'glosa' => 'Envio procesado',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiSendStatusService(
            app(
                SiiRequestThrottleService::class
            )
        );

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            rutBody: '76123456',
            rutDv: '7',
            trackId: '1234567890'
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'Cookie',
                'TOKEN=TOKEN-DE-PRUEBA'
            );
        });
    }
    public function test_query_recibe_el_rut_de_la_empresa_para_construir_la_consulta_de_estado(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiSendStatusService::class
        );

        $method = $reflection->getMethod(
            'query'
        );

        $parameterNames = array_map(
            static fn ($parameter) => $parameter->getName(),
            $method->getParameters()
        );

        $this->assertContains(
            'rutBody',
            $parameterNames,
            'query() debe recibir el cuerpo del RUT de la empresa.'
        );

        $this->assertContains(
            'rutDv',
            $parameterNames,
            'query() debe recibir el dígito verificador del RUT de la empresa.'
        );
    }
    public function test_query_consulta_el_estado_mediante_get_y_ruta_rut_dv_trackid(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_status_url',
            'https://example.test/recursos/v1/boleta.electronica.envio/'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'estado' => 'EPR',
                    'glosa' => 'Envio procesado',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiSendStatusService(
            app(
                SiiRequestThrottleService::class
            )
        );

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            rutBody: '76123456',
            rutDv: '7',
            trackId: '1234567890'
        );

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url()
                    === 'https://example.test/recursos/v1/boleta.electronica.envio/76123456-7-1234567890';
        });
    }
    public function test_query_envia_un_header_accept_valido(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_status_url',
            'https://example.test/recursos/v1/boleta.electronica.envio/'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'estado' => 'EPR',
                    'glosa' => 'Envio procesado',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiSendStatusService(
            app(
                SiiRequestThrottleService::class
            )
        );

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            rutBody: '76123456',
            rutDv: '7',
            trackId: '1234567890'
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'Accept',
                'application/json, application/xml, text/plain'
            );
        });
    }
    public function test_query_no_depende_de_la_configuracion_legacy_del_header_de_token(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_status_url',
            'https://example.test/recursos/v1/boleta.electronica.envio/'
        );

        config()->set(
            'dte.sii.boleta.cert.token_header_name',
            null
        );

        config()->set(
            'dte.sii.boleta.cert.token_header_prefix',
            null
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'estado' => 'EPR',
                    'glosa' => 'Envio procesado',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiSendStatusService(
            app(
                SiiRequestThrottleService::class
            )
        );

        $service->query(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            rutBody: '76123456',
            rutDv: '7',
            trackId: '1234567890'
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'Cookie',
                'TOKEN=TOKEN-DE-PRUEBA'
            );
        });
    }
}
