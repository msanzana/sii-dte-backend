<?php

namespace Tests\Unit;

use App\Modules\Dte\Application\UseCases\Dispatch\SendSignedBoletaToSiiUseCase;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiUploadService;
use App\Modules\Dte\Infrastructure\Sii\SiiRequestThrottleService;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

final class SiiBoletaApiUploadServiceTest extends TestCase
{

    public function test_aplica_throttle_antes_de_enviar_la_boleta_al_sii(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiUploadService::class
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
            'SiiBoletaApiUploadService debe recibir SiiRequestThrottleService.'
        );

        $method = $reflection->getMethod(
            'upload'
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
            'upload() debe aplicar throttle exactamente una vez por envío.'
        );

        $throttlePosition = strpos(
            $methodSource,
            $throttleCall
        );

        $sendPosition = strpos(
            $methodSource,
            '$response = match'
        );

        $this->assertIsInt(
            $throttlePosition
        );

        $this->assertIsInt(
            $sendPosition
        );

        $this->assertLessThan(
            $sendPosition,
            $throttlePosition,
            'El throttle debe ejecutarse antes del envío HTTP de la boleta.'
        );
    }
    public function test_upload_usa_la_configuracion_real_de_boleta_y_cookie_token(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_url',
            'https://example.test/recursos/v1/boleta.electronica.envio'
        );

        config()->set(
            'dte.sii.boleta.cert.send_http_method',
            'POST'
        );

        config()->set(
            'dte.sii.boleta.cert.send_mode',
            'raw_xml'
        );

        config()->set(
            'dte.sii.boleta.cert.send_content_type',
            'application/xml; charset=UTF-8'
        );

        config()->set(
            'dte.sii.boleta.cert.send_body_field',
            'xml'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'trackid' => 123456,
                    'estado' => 'REC',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiUploadService(
            app(SiiRequestThrottleService::class)
        );

        $result = $service->upload(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            filename: 'envio_boleta.xml',
            xmlPayload: '<EnvioBOLETA></EnvioBOLETA>'
        );

        $this->assertSame(
            '123456',
            $result['track_id']
        );

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://example.test/recursos/v1/boleta.electronica.envio'
                && $request->method() === 'POST'
                && $request->hasHeader(
                    'Cookie',
                    'TOKEN=TOKEN-DE-PRUEBA'
                );
        });
    }
    public function test_upload_multipart_envia_los_campos_oficiales_del_sii_y_el_archivo_xml(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_url',
            'https://example.test/recursos/v1/boleta.electronica.envio'
        );

        config()->set(
            'dte.sii.boleta.cert.send_http_method',
            'POST'
        );

        config()->set(
            'dte.sii.boleta.cert.send_mode',
            'multipart_xml'
        );

        config()->set(
            'dte.sii.boleta.cert.send_content_type',
            'multipart/form-data'
        );

        config()->set(
            'dte.sii.boleta.cert.send_body_field',
            'archivo'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'trackid' => '1234567890',
                    'estado' => 'REC',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiUploadService(
            app(SiiRequestThrottleService::class)
        );

        $service->upload(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            senderRutBody: '11111111',
            senderRutDv: '1',
            companyRutBody: '76123456',
            companyRutDv: '7',
            filename: 'envio_boleta.xml',
            xmlPayload: '<EnvioBOLETA>PRUEBA</EnvioBOLETA>'
        );

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->hasHeader(
                    'Cookie',
                    'TOKEN=TOKEN-DE-PRUEBA'
                )
                && $request->isMultipart()
                && $request->hasFile(
                    'rutSender',
                    '11111111'
                )
                && $request->hasFile(
                    'dvSender',
                    '1'
                )
                && $request->hasFile(
                    'rutCompany',
                    '76123456'
                )
                && $request->hasFile(
                    'dvCompany',
                    '7'
                )
                && $request->hasFile(
                    'archivo',
                    '<EnvioBOLETA>PRUEBA</EnvioBOLETA>',
                    'envio_boleta.xml'
                );
        });
    }
    public function test_la_configuracion_cert_de_boleta_activa_el_upload_multipart(): void
    {
        $this->assertSame(
            'multipart_xml',
            config(
                'dte.sii.boleta.cert.send_mode'
            ),
            'El ambiente cert debe utilizar el transporte multipart para enviar boletas.'
        );

        $this->assertSame(
            'archivo',
            config(
                'dte.sii.boleta.cert.send_body_field'
            ),
            'El archivo XML debe enviarse utilizando el campo archivo.'
        );

        $this->assertSame(
            'POST',
            config(
                'dte.sii.boleta.cert.send_http_method'
            )
        );

        $this->assertNotSame(
            '',
            trim(
                (string) config(
                    'dte.sii.boleta.cert.send_url'
                )
            ),
            'El endpoint de envío de boletas de certificación no puede estar vacío.'
        );
    }
    public function test_la_configuracion_prod_de_boleta_usa_su_propia_variable_de_entorno_para_send_mode(): void
    {
        $configSource = file_get_contents(
            config_path(
                'dte.php'
            )
        );

        $this->assertIsString(
            $configSource
        );

        $certVariable =
            "env('DTE_SII_BOLETA_CERT_SEND_MODE'";

        $prodVariable =
            "env('DTE_SII_BOLETA_PROD_SEND_MODE'";

        $this->assertSame(
            1,
            substr_count(
                $configSource,
                $certVariable
            ),
            'DTE_SII_BOLETA_CERT_SEND_MODE debe utilizarse únicamente en la configuración cert.'
        );

        $this->assertStringContainsString(
            $prodVariable,
            $configSource,
            'La configuración prod debe leer DTE_SII_BOLETA_PROD_SEND_MODE.'
        );
    }
    public function test_upload_multipart_envia_user_agent_compatible_con_pangal(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_url',
            'https://example.test/recursos/v1/boleta.electronica.envio'
        );

        config()->set(
            'dte.sii.boleta.cert.send_http_method',
            'POST'
        );

        config()->set(
            'dte.sii.boleta.cert.send_mode',
            'multipart_xml'
        );

        config()->set(
            'dte.sii.boleta.cert.send_content_type',
            'multipart/form-data'
        );

        config()->set(
            'dte.sii.boleta.cert.send_body_field',
            'archivo'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                [
                    'trackid' => '1234567890',
                    'estado' => 'REC',
                ],
                200
            ),
        ]);

        $service = new SiiBoletaApiUploadService(
            app(SiiRequestThrottleService::class)
        );

        $service->upload(
            environment: 'cert',
            token: 'TOKEN-DE-PRUEBA',
            senderRutBody: '15259615',
            senderRutDv: '4',
            companyRutBody: '77760724',
            companyRutDv: '3',
            filename: 'envio_boleta.xml',
            xmlPayload: '<EnvioBOLETA>PRUEBA</EnvioBOLETA>'
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader(
                'User-Agent',
                'Mozilla/4.0 (compatible; PROG 1.0; Laravel DTE Client)'
            );
        });
    }
    public function test_upload_preserva_http_status_y_body_cuando_pangal_responde_error(): void
    {
        config()->set(
            'dte.sii.boleta.cert.send_url',
            'https://example.test/recursos/v1/boleta.electronica.envio'
        );

        config()->set(
            'dte.sii.boleta.cert.send_http_method',
            'POST'
        );

        config()->set(
            'dte.sii.boleta.cert.send_mode',
            'multipart_xml'
        );

        config()->set(
            'dte.sii.boleta.cert.send_content_type',
            'multipart/form-data'
        );

        config()->set(
            'dte.sii.boleta.cert.send_body_field',
            'archivo'
        );

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        Http::fake([
            '*' => Http::response(
                "ERROR REAL DE PANGAL\n",
                400,
                [
                    'Content-Type' => 'text/plain',
                ]
            ),
        ]);

        $service = new SiiBoletaApiUploadService(
            app(SiiRequestThrottleService::class)
        );

        try {

            $service->upload(
                environment: 'cert',
                token: 'TOKEN-DE-PRUEBA',
                senderRutBody: '15259615',
                senderRutDv: '4',
                companyRutBody: '77760724',
                companyRutDv: '3',
                filename: 'envio_boleta.xml',
                xmlPayload: '<EnvioBOLETA>PRUEBA</EnvioBOLETA>'
            );

            $this->fail(
                'Se esperaba SiiBoletaSendException.'
            );

        } catch (\App\Modules\Dte\Domain\Exceptions\SiiBoletaSendException $e) {

            $this->assertSame(
                400,
                $e->httpStatus()
            );

            $this->assertSame(
                "ERROR REAL DE PANGAL\n",
                $e->rawBody()
            );

            $this->assertStringContainsString(
                'HTTP 400',
                $e->getMessage()
            );
        }
    }
}
