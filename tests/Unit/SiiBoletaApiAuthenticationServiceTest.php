<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiSeedXmlSignerService;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionMethod;
use Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use App\Modules\Dte\Infrastructure\Sii\SiiRequestThrottleService;

final class SiiBoletaApiAuthenticationServiceTest extends TestCase
{
    public function test_la_autenticacion_de_boleta_usa_el_firmador_compartido_y_recibe_el_exponente_rsa(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiAuthenticationService::class
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
            SiiSeedXmlSignerService::class,
            $constructorTypes,
            'SiiBoletaApiAuthenticationService debe recibir SiiSeedXmlSignerService por inyección de dependencias.'
        );

        $authenticateMethod = $reflection->getMethod(
            'authenticate'
        );

        $parameterNames = array_map(
            static fn ($parameter) => $parameter->getName(),
            $authenticateMethod->getParameters()
        );

        $this->assertContains(
            'exponentBase64',
            $parameterNames,
            'authenticate() debe recibir exponentBase64.'
        );

        $source = file_get_contents(
            app_path(
                'Modules/Dte/Infrastructure/Sii/SiiBoletaApiAuthenticationService.php'
            )
        );

        $this->assertMatchesRegularExpression(
            '/\$signedSeedXml\s*=\s*\$this->seedXmlSignerService->sign\s*\(/s',
            $source
        );
    }
    public function test_extract_flexible_value_extrae_un_valor_desde_json_anidado(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiAuthenticationService::class
        );

        $service = $reflection->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(
            SiiBoletaApiAuthenticationService::class,
            'extractFlexibleValue'
        );

        $method->setAccessible(true);

        $body = json_encode([
            'respuesta' => [
                'datos' => [
                    'semilla' => '030530912644',
                ],
            ],
        ]);

        $this->assertIsString(
            $body
        );

        $value = $method->invoke(
            $service,
            $body,
            [
                'semilla',
                'SEMILLA',
                'seed',
                'Seed',
            ]
        );

        $this->assertSame(
            '030530912644',
            $value
        );
    }
    public function test_extract_flexible_value_mantiene_el_fallback_xml(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiAuthenticationService::class
        );

        $service = $reflection->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(
            SiiBoletaApiAuthenticationService::class,
            'extractFlexibleValue'
        );

        $method->setAccessible(true);

        $body = <<<'XML'
    <?xml version="1.0" encoding="UTF-8"?>
    <RESPUESTA>
        <SEMILLA>030530912644</SEMILLA>
    </RESPUESTA>
    XML;

        $value = $method->invoke(
            $service,
            $body,
            [
                'semilla',
                'SEMILLA',
                'seed',
                'Seed',
            ]
        );

        $this->assertSame(
            '030530912644',
            $value
        );
    }
    public function test_authenticate_obtiene_semilla_firma_y_recupera_token_sin_contactar_al_sii(): void
    {
        config()->set(
            'dte.sii.boleta.cert.seed_url',
            'https://sii.test/seed'
        );

        config()->set(
            'dte.sii.boleta.cert.token_url',
            'https://sii.test/token'
        );

        Http::fake([
            'https://sii.test/seed' => Http::response(
                [
                    'respuesta' => [
                        'semilla' => '030530912644',
                    ],
                ],
                200,
                [
                    'Content-Type' => 'application/json',
                ]
            ),

            'https://sii.test/token' => Http::response(
                [
                    'respuesta' => [
                        'token' => 'TOKEN-BOLETA-PRUEBA',
                    ],
                ],
                200,
                [
                    'Content-Type' => 'application/json',
                ]
            ),
        ]);

        $privateKeyPem = <<<'PEM'
    -----BEGIN PRIVATE KEY-----
    MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKqu3RItkI6Cj/Qg
    xEmdYIxqYkrrbNRVTIAWqOFGMW7aIR9P/9GTpt625BPNoilu1DfEMMcJyQwYLL1D
    Rnap8+W3tCHOBVjCW+ztdRneUxKK9MBUPwX2H14tzhwv/Ugg2yAwxx1WgDElc1v/
    DonO5P0qk0m1ByuDlLMHDbUDRu/JAgMBAAECgYBjsg/e9k5hb1G2Pw1oEky6t8kC
    CdFflRNCHfo221E0dqSyLYA3Yg8uN5WxG4OEv/+lMytqlwSf098ODaWy2kJjDrck
    vFixcJV1j+RS1AMcxFJihjNF6LpBXpcZmqvVUxEx2ps0NeLQU82JnWRts1pTYQbq
    50Fn1JbmFv2eAv46oQJBAOD9zrzsXFmaQqqcakh/L+X/lWvP6xsOeNobUF0X1Q8h
    AuZX/vc2wIrlDO4F/S98rUjQDVoGKjF8ThKHhALpXPcCQQDCNO/oL9hyxLodb9wz
    1BgYXS4q5XldSQD7JuQZ6JsmA3v2FVpJFzb+MCpKxgcIiSt6ptqs/c0eGe2OXezx
    1qk/AkBqfjfYnFep4aYkcxyra+gUCUGEYkl56QOy2LLVHW6vVoS02nnIMZY5J+lS
    0GriizTJ/hATyE84VQnvI02Mw0BJAkAhDYVvTQVXsyfB7tHZeFWJgAJlhpy7RbuH
    Az17M12EgL9OSKAPJIZViLkJ9N4pk770pwU8wA1y/BK0UkQLfO9dAkEAtQw4ULhE
    4aaktuqR7lb8YmxfhCR2WJN/7qSJlQw3Oir1M9CaN18RmU6tX4zUYs0Yj7p54vaa
    oCKeFCoCFRPA7A==
    -----END PRIVATE KEY-----
    PEM;

        $modulusBase64 =
            'qq7dEi2QjoKP9CDESZ1gjGpiSuts1FVMgBao4UYxbtohH0//0ZOm3rbkE82iKW7UN8QwxwnJDBgsvUNGdqnz5be0Ic4FWMJb7O11Gd5TEor0wFQ/BfYfXi3OHC/9SCDbIDDHHVaAMSVzW/8Oic7k/SqTSbUHK4OUswcNtQNG78k=';

        config()->set(
            'dte.sii.transport.minimum_request_interval_ms',
            0
        );

        $service = new SiiBoletaApiAuthenticationService(
            new SiiSeedXmlSignerService(),
            app(SiiRequestThrottleService::class)
        );

        $token = $service->authenticate(
            environment: 'cert',
            privateKeyPem: $privateKeyPem,
            certificateBase64: 'VEVTVF9DRVJUSUZJQ0FURQ==',
            modulusBase64: $modulusBase64,
            exponentBase64: 'AQAB'
        );

        $this->assertSame(
            'TOKEN-BOLETA-PRUEBA',
            $token
        );

        Http::assertSentCount(2);

        Http::assertSent(
            fn (Request $request): bool =>
                $request->method() === 'GET'
                && $request->url() === 'https://sii.test/seed'
        );

        Http::assertSent(
            function (Request $request): bool {
                if (
                    $request->method() !== 'POST'
                    || $request->url() !== 'https://sii.test/token'
                ) {
                    return false;
                }

                $body = $request->body();

                return str_contains(
                    $body,
                    '<Semilla>030530912644</Semilla>'
                )
                    && str_contains(
                        $body,
                        '<DigestValue>l2s9BqLppHaWo+w1Al1J5SsYScs=</DigestValue>'
                    )
                    && str_contains(
                        $body,
                        '<Exponent>AQAB</Exponent>'
                    )
                    && str_contains(
                        $body,
                        '<SignatureValue>'
                    );
            }
        );
    }
    public function test_authenticate_aplica_throttle_antes_de_semilla_y_token(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaApiAuthenticationService::class
        );

        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor
        );

        $constructorTypes = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType) {
                $constructorTypes[] = $type->getName();
            }
        }

        $this->assertContains(
            SiiRequestThrottleService::class,
            $constructorTypes,
            'SiiBoletaApiAuthenticationService debe recibir SiiRequestThrottleService.'
        );

        $method = $reflection->getMethod(
            'authenticate'
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

        $throttleCall = '$this->requestThrottleService->wait($environment);';

        $this->assertSame(
            2,
            substr_count(
                $methodSource,
                $throttleCall
            ),
            'authenticate() debe aplicar throttle exactamente antes de las dos solicitudes HTTP.'
        );

        $firstThrottlePosition = strpos(
            $methodSource,
            $throttleCall
        );

        $seedRequestPosition = strpos(
            $methodSource,
            'Http::get($seedUrl)'
        );

        $secondThrottlePosition = strpos(
            $methodSource,
            $throttleCall,
            $firstThrottlePosition + 1
        );

        $tokenRequestPosition = strpos(
            $methodSource,
            '->post($tokenUrl)'
        );

        $this->assertIsInt(
            $firstThrottlePosition
        );

        $this->assertIsInt(
            $seedRequestPosition
        );

        $this->assertIsInt(
            $secondThrottlePosition
        );

        $this->assertIsInt(
            $tokenRequestPosition
        );

        $this->assertLessThan(
            $seedRequestPosition,
            $firstThrottlePosition,
            'El primer throttle debe ejecutarse antes de solicitar la semilla.'
        );

        $this->assertLessThan(
            $tokenRequestPosition,
            $secondThrottlePosition,
            'El segundo throttle debe ejecutarse antes de solicitar el token.'
        );

        $this->assertGreaterThan(
            $seedRequestPosition,
            $secondThrottlePosition,
            'El segundo throttle debe ocurrir después de la solicitud de semilla.'
        );
    }
}