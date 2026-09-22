<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadStatusService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SiiFacturaUploadStatusServiceTest extends TestCase
{
    public function test_aplica_el_throttle_antes_de_enviar_query_est_up(): void
    {
        config()->set(
            'dte.sii.transport.cache_store',
            'file'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            1
        );

        config()->set(
            'dte.sii.cert.soap.query_est_up_url',
            'https://sii.test/QueryEstUp.jws'
        );

        $cache = Cache::store('file');

        $lastRequestKey =
            'sii:transport:cert:last-request-at';

        $cache->forget($lastRequestKey);

        Http::fake(
            function (Request $request) use (
                $cache,
                $lastRequestKey
            ) {
                /*
                 * Cuando el POST llega hasta aquí,
                 * el throttle ya debería haber registrado
                 * la solicitud.
                 */
                $this->assertNotNull(
                    $cache->get($lastRequestKey),
                    'QueryEstUp ejecutó el POST antes de pasar por SiiRequestThrottleService.'
                );

                return Http::response(
                    '<RESPUESTA><ESTADO>EPR</ESTADO><GLOSA>Envio Procesado</GLOSA></RESPUESTA>',
                    200,
                    [
                        'Content-Type' =>
                            'text/xml; charset=UTF-8',
                    ]
                );
            }
        );

        try {
            $service = app(
                SiiFacturaUploadStatusService::class
            );

            $result = $service->query(
                environment: 'cert',
                token: 'TOKEN_PRUEBA',
                companyRutBody: '77760724',
                companyRutDv: '3',
                trackId: '0254844840'
            );

            $this->assertSame(
                'EPR',
                $result['estado']
            );

            $this->assertSame(
                'Envio Procesado',
                $result['glosa']
            );

            Http::assertSentCount(1);
        } finally {
            $cache->forget($lastRequestKey);
        }
    }
    public function test_rechaza_una_respuesta_http_no_exitosa_de_query_est_up(): void
    {
        config()->set(
            'dte.sii.transport.cache_store',
            'file'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            1
        );

        config()->set(
            'dte.sii.cert.soap.query_est_up_url',
            'https://sii.test/QueryEstUp.jws'
        );

        $cache = Cache::store('file');

        $lastRequestKey =
            'sii:transport:cert:last-request-at';

        $cache->forget($lastRequestKey);

        Http::fake([
            'https://sii.test/QueryEstUp.jws' =>
                Http::response(
                    'ERROR INTERNO SIMULADO',
                    500
                ),
        ]);

        try {
            $service = app(
                SiiFacturaUploadStatusService::class
            );

            $service->query(
                environment: 'cert',
                token: 'TOKEN_PRUEBA',
                companyRutBody: '77760724',
                companyRutDv: '3',
                trackId: '0254844840'
            );

            $this->fail(
                'QueryEstUp no rechazó una respuesta HTTP 500.'
            );
        } catch (SiiUploadException $e) {
            $this->assertSame(
                'La consulta QueryEstUp respondió con HTTP 500',
                $e->getMessage()
            );

            Http::assertSentCount(1);
        } finally {
            $cache->forget($lastRequestKey);
        }
    }
}
