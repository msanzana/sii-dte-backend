<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaDocumentStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadStatusService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SiiFacturaDocumentStatusServiceTest extends TestCase
{
    public function test_aplica_el_throttle_antes_de_enviar_query_est_dte(): void
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
            'dte.sii.cert.soap.query_est_dte_url',
            'https://sii.test/QueryEstDte.jws'
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
                 * Cuando QueryEstDte llegue al POST,
                 * el throttle ya debe haber registrado
                 * la solicitud.
                 */
                $this->assertNotNull(
                    $cache->get($lastRequestKey),
                    'QueryEstDte ejecutó el POST antes de pasar por SiiRequestThrottleService.'
                );

                $response = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>
        <getEstDteResponse>
            <getEstDteReturn>&lt;RESPUESTA&gt;&lt;RESP_HDR&gt;&lt;ESTADO&gt;DOK&lt;/ESTADO&gt;&lt;GLOSA&gt;Documento Recibido&lt;/GLOSA&gt;&lt;/RESP_HDR&gt;&lt;/RESPUESTA&gt;</getEstDteReturn>
        </getEstDteResponse>
    </soapenv:Body>
</soapenv:Envelope>
XML;

                return Http::response(
                    $response,
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
                SiiFacturaDocumentStatusService::class
            );

            $result = $service->query(
                environment: 'cert',
                consultantRutBody: '11111111',
                consultantRutDv: '1',
                companyRutBody: '77760724',
                companyRutDv: '3',
                receiverRutBody: '22222222',
                receiverRutDv: '2',
                dteType: '33',
                folio: '23',
                issueDate: '01092026',
                amount: '1000',
                token: 'TOKEN_PRUEBA'
            );

            $this->assertSame(
                'DOK',
                $result['estado']
            );

            $this->assertSame(
                'Documento Recibido',
                $result['glosa']
            );

            Http::assertSentCount(1);
        } finally {
            $cache->forget($lastRequestKey);
        }
    }
}
