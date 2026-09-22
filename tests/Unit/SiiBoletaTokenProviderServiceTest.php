<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;
use App\Modules\Dte\Infrastructure\Sii\SiiSeedXmlSignerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use App\Modules\Dte\Infrastructure\Sii\SiiRequestThrottleService;

final class SiiBoletaTokenProviderServiceTest extends TestCase
{
    public function test_el_proveedor_de_token_de_boleta_mantiene_el_contrato_del_proveedor_de_factura(): void
    {
        $this->assertTrue(
            class_exists(SiiBoletaTokenProviderService::class),
            'Debe existir SiiBoletaTokenProviderService.'
        );

        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
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
            SiiBoletaApiAuthenticationService::class,
            $constructorTypes,
            'El proveedor debe delegar la generación real del token en SiiBoletaApiAuthenticationService.'
        );

        $this->assertTrue(
            $reflection->hasMethod('get'),
            'SiiBoletaTokenProviderService debe exponer get().'
        );

        $method = $reflection->getMethod(
            'get'
        );

        $parameterNames = array_map(
            static fn ($parameter) => $parameter->getName(),
            $method->getParameters()
        );

        $this->assertSame(
            [
                'environment',
                'companyId',
                'certificateId',
                'privateKeyPem',
                'certificateBase64',
                'modulusBase64',
                'exponentBase64',
            ],
            $parameterNames
        );

        $returnType = $method->getReturnType();

        $this->assertInstanceOf(
            ReflectionNamedType::class,
            $returnType
        );

        $this->assertSame(
            'array',
            $returnType->getName()
        );
    }
    public function test_cache_key_separa_ambiente_empresa_y_certificado(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
        );

        $this->assertTrue(
            $reflection->hasMethod('cacheKey'),
            'SiiBoletaTokenProviderService debe tener un cacheKey propio.'
        );

        $method = $reflection->getMethod(
            'cacheKey'
        );

        $method->setAccessible(true);

        $service = $reflection->newInstanceWithoutConstructor();

        $baseKey = $method->invoke(
            $service,
            ' CERT ',
            1,
            10
        );

        $this->assertSame(
            'sii:boleta:token:cert:company:1:certificate:10',
            $baseKey
        );

        $this->assertSame(
            $baseKey,
            $method->invoke(
                $service,
                'cert',
                1,
                10
            ),
            'La misma combinación debe producir siempre la misma clave.'
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'prod',
                1,
                10
            ),
            'Ambientes distintos no pueden compartir token.'
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'cert',
                2,
                10
            ),
            'Empresas distintas no pueden compartir token.'
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'cert',
                1,
                11
            ),
            'Certificados distintos no pueden compartir token.'
        );
    }
    public function test_devuelve_el_token_desde_cache_sin_autenticarse_nuevamente(): void
    {
        $cacheKey = 'sii:boleta:token:cert:company:1:certificate:10';

        Cache::store('file')->forget(
            $cacheKey
        );

        Cache::store('file')->put(
            $cacheKey,
            'TOKEN-BOLETA-CACHE',
            now()->addMinutes(5)
        );

        config()->set(
            'dte.sii.boleta.cert.seed_url',
            'https://sii.test/seed'
        );

        config()->set(
            'dte.sii.boleta.cert.token_url',
            'https://sii.test/token'
        );

        /*
        * Si el proveedor intenta autenticarse nuevamente,
        * cualquier petición HTTP hará fallar inmediatamente
        * este test.
        */
        Http::preventStrayRequests();

        try {
            $service = new SiiBoletaTokenProviderService(
                    new SiiBoletaApiAuthenticationService(
                    new SiiSeedXmlSignerService(),
                    app(SiiRequestThrottleService::class)
                )
            );

            $result = $service->get(
                environment: 'cert',
                companyId: 1,
                certificateId: 10,
                privateKeyPem: 'NO-DEBE-UTILIZARSE',
                certificateBase64: 'NO-DEBE-UTILIZARSE',
                modulusBase64: 'NO-DEBE-UTILIZARSE',
                exponentBase64: 'NO-DEBE-UTILIZARSE'
            );

            $this->assertSame(
                'TOKEN-BOLETA-CACHE',
                $result['token']
            );

            $this->assertSame(
                'cache',
                $result['source']
            );

            Http::assertNothingSent();
        } finally {
            Cache::store('file')->forget(
                $cacheKey
            );
        }
    }
    public function test_usa_la_configuracion_correcta_para_el_ttl_del_token_cacheado(): void
    {
        config()->set(
            'dte.sii.transport.token_cache_ttl_minutes',
            37
        );

        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
        );

        $this->assertTrue(
            $reflection->hasMethod('cacheTtlMinutes'),
            'SiiBoletaTokenProviderService debe resolver el TTL desde la configuración de transporte SII.'
        );

        $method = $reflection->getMethod(
            'cacheTtlMinutes'
        );

        $method->setAccessible(true);

        $service = $reflection->newInstanceWithoutConstructor();

        $this->assertSame(
            37,
            $method->invoke($service)
        );
    }
    public function test_si_no_hay_token_en_cache_autentica_y_lo_guarda_con_el_ttl_configurado(): void
    {
        config()->set(
            'dte.sii.transport.token_cache_ttl_minutes',
            37
        );

        $cacheKey =
            'sii:boleta:token:cert:company:1:certificate:10';

        Cache::store('file')->forget(
            $cacheKey
        );

        $start = Carbon::parse(
            '2026-09-11 12:00:00'
        );

        Carbon::setTestNow(
            $start
        );

        $authenticationService =
            new class(
                new SiiSeedXmlSignerService(),
                app(SiiRequestThrottleService::class)
            )
                extends SiiBoletaApiAuthenticationService
            {
                public int $authenticateCalls = 0;

                public function authenticate(
                    string $environment,
                    string $privateKeyPem,
                    string $certificateBase64,
                    string $modulusBase64,
                    string $exponentBase64
                ): string {
                    $this->authenticateCalls++;

                    return 'TOKEN-BOLETA-GENERADO';
                }
            };

        try {
            $service = new SiiBoletaTokenProviderService(
                $authenticationService
            );

            $result = $service->get(
                environment: 'cert',
                companyId: 1,
                certificateId: 10,
                privateKeyPem: 'PRIVATE-KEY-TEST',
                certificateBase64: 'CERTIFICATE-TEST',
                modulusBase64: 'MODULUS-TEST',
                exponentBase64: 'AQAB'
            );

            $this->assertSame(
                'TOKEN-BOLETA-GENERADO',
                $result['token']
            );

            $this->assertSame(
                'authentication',
                $result['source']
            );

            $this->assertSame(
                1,
                $authenticationService->authenticateCalls
            );

            $this->assertSame(
                'TOKEN-BOLETA-GENERADO',
                Cache::store('file')->get($cacheKey),
                'El token generado debe quedar almacenado en cache.'
            );

            Carbon::setTestNow(
                $start->copy()->addMinutes(36)
            );

            $this->assertSame(
                'TOKEN-BOLETA-GENERADO',
                Cache::store('file')->get($cacheKey),
                'El token debe seguir vigente antes de cumplir el TTL.'
            );

            Carbon::setTestNow(
                $start->copy()->addMinutes(38)
            );

            $this->assertNull(
                Cache::store('file')->get($cacheKey),
                'El token debe expirar después del TTL configurado.'
            );
        } finally {
            Carbon::setTestNow();

            Cache::store('file')->forget(
                $cacheKey
            );
        }
    }
    public function test_la_generacion_del_token_se_protege_con_un_lock_de_90_segundos(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
        );

        $method = $reflection->getMethod(
            'get'
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

        $this->assertMatchesRegularExpression(
            "/->lock\\(\\s*\\\$lockKey\\s*,\\s*90\\s*\\)/",
            $methodSource,
            'La generación del token de boleta debe protegerse con un lock de 90 segundos.'
        );

        $this->assertMatchesRegularExpression(
            "/->block\\(\\s*30\\s*,/",
            $methodSource,
            'El proveedor debe esperar como máximo 30 segundos para adquirir el lock.'
        );
    }
    public function test_revisa_nuevamente_el_cache_despues_de_obtener_el_lock(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
        );

        $method = $reflection->getMethod(
            'get'
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

        $this->assertStringContainsString(
            '$cachedTokenAfterLock = Cache::store(\'file\')->get(',
            $methodSource,
            'Debe existir una segunda lectura de cache después de adquirir el lock.'
        );

        $secondCachePosition = strpos(
            $methodSource,
            '$cachedTokenAfterLock = Cache::store(\'file\')->get('
        );

        $authenticationPosition = strpos(
            $methodSource,
            '$this->authenticationService->authenticate('
        );

        $this->assertIsInt(
            $secondCachePosition
        );

        $this->assertIsInt(
            $authenticationPosition
        );

        $this->assertLessThan(
            $authenticationPosition,
            $secondCachePosition,
            'La segunda lectura de cache debe ocurrir antes de generar un nuevo token.'
        );
    }
    public function test_si_expira_el_lock_revisa_el_cache_y_no_autentica_fuera_del_lock(): void
    {
        $reflection = new ReflectionClass(
            SiiBoletaTokenProviderService::class
        );

        $method = $reflection->getMethod(
            'get'
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

        $this->assertStringContainsString(
            'catch (LockTimeoutException',
            $methodSource,
            'El timeout del lock debe manejarse explícitamente.'
        );

        $catchPosition = strpos(
            $methodSource,
            'catch (LockTimeoutException'
        );

        $this->assertIsInt(
            $catchPosition
        );

        $sourceAfterCatch = substr(
            $methodSource,
            $catchPosition
        );

        $this->assertStringContainsString(
            '$cachedTokenAfterTimeout = Cache::store(\'file\')->get(',
            $sourceAfterCatch,
            'Después de un timeout debe revisarse nuevamente el cache.'
        );

        $this->assertStringNotContainsString(
            '$this->authenticationService->authenticate(',
            $sourceAfterCatch,
            'Nunca debe generarse un token fuera del lock después de un timeout.'
        );

        $this->assertStringContainsString(
            'throw new RuntimeException(',
            $sourceAfterCatch,
            'Si el cache sigue vacío después del timeout debe abortarse la operación.'
        );
    }
}
