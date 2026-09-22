<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Sii\SiiTokenProviderService;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;


final class SiiTokenProviderServiceTest extends TestCase
{
    public function test_usa_la_clave_correcta_para_el_ttl_del_token_cacheado(): void
    {
        $source = file_get_contents(
            app_path('Modules/Dte/Infrastructure/Sii/SiiTokenProviderService.php')
        );

        $this->assertStringContainsString(
            "'dte.sii.transport.token_cache_ttl_minutes'",
            $source
        );

        $this->assertStringNotContainsString(
            "'dte.sii.transport.token_ttl_minutes'",
            $source
        );
    }
    public function test_revisa_nuevamente_el_cache_despues_de_obtener_el_lock(): void
    {
        config()->set('dte.sii.transport.cache_store', 'file');

        /*
        * Si por un error el servicio intenta autenticar realmente,
        * estas URLs vacías harán que falle antes de contactar al SII.
        */
        config()->set('dte.sii.cert.soap.seed_url', '');
        config()->set('dte.sii.cert.soap.token_url', '');

        $cacheKey = 'sii:token:cert:company:1:certificate:3';

        $encryptedToken = Crypt::encryptString(
            'TOKEN_GENERADO_POR_OTRO_WORKER'
        );

        $cache = Mockery::mock(CacheRepository::class);

        $lockStore = Mockery::mock(LockProvider::class);

        $lock = Mockery::mock(Lock::class);

        Cache::shouldReceive('store')
            ->once()
            ->with('file')
            ->andReturn($cache);

        $cache->shouldReceive('getStore')
            ->once()
            ->andReturn($lockStore);

        /*
        * Primera lectura:
        * todavía no existe TOKEN.
        *
        * Segunda lectura:
        * mientras esperábamos el lock otro worker ya lo generó.
        */
        $cache->shouldReceive('get')
            ->with($cacheKey)
            ->andReturn(
                null,
                $encryptedToken
            );

        $lockStore->shouldReceive('lock')
            ->once()
            ->with(
                $cacheKey . ':lock',
                Mockery::type('int')
            )
            ->andReturn($lock);

        $lock->shouldReceive('block')
            ->once()
            ->andReturnUsing(
                function (int $seconds, callable $callback) {
                    return $callback();
                }
            );

        $service = app(SiiTokenProviderService::class);

        try {
            $result = $service->get(
                environment: 'cert',
                companyId: 1,
                certificateId: 3,
                privateKeyPem: 'NO_DEBE_USARSE',
                certificateBase64: 'NO_DEBE_USARSE',
                modulusBase64: 'NO_DEBE_USARSE',
                exponentBase64: 'NO_DEBE_USARSE',
            );
        } catch (\Throwable $e) {
            $this->fail(
                'El servicio intentó generar un TOKEN nuevo en vez de reutilizar el creado mientras esperaba el lock. Error: '
                . $e->getMessage()
            );
        }

        $this->assertSame(
            'TOKEN_GENERADO_POR_OTRO_WORKER',
            $result['token']
        );

        $this->assertSame(
            'cache',
            $result['source']
        );
    }
    public function test_revisa_el_cache_si_el_lock_expira_antes_de_generar_otro_token(): void
    {
        config()->set('dte.sii.transport.cache_store', 'file');

        /*
        * Impide cualquier contacto real con el SII.
        * Si intenta autenticar, fallará inmediatamente.
        */
        config()->set('dte.sii.cert.soap.seed_url', '');
        config()->set('dte.sii.cert.soap.token_url', '');

        $cacheKey = 'sii:token:cert:company:1:certificate:3';

        $encryptedToken = Crypt::encryptString(
            'TOKEN_APARECIDO_DURANTE_TIMEOUT'
        );

        $cache = Mockery::mock(CacheRepository::class);
        $lockStore = Mockery::mock(LockProvider::class);
        $lock = Mockery::mock(Lock::class);

        Cache::shouldReceive('store')
            ->once()
            ->with('file')
            ->andReturn($cache);

        $cache->shouldReceive('getStore')
            ->once()
            ->andReturn($lockStore);

        /*
        * Primera lectura: no existe.
        * Después del timeout: otro worker ya lo dejó disponible.
        */
        $cache->shouldReceive('get')
            ->with($cacheKey)
            ->andReturn(
                null,
                $encryptedToken
            );

        $lockStore->shouldReceive('lock')
            ->once()
            ->with(
                $cacheKey . ':lock',
                Mockery::type('int')
            )
            ->andReturn($lock);

        $lock->shouldReceive('block')
            ->once()
            ->andThrow(new LockTimeoutException());

        $service = app(SiiTokenProviderService::class);

        try {
            $result = $service->get(
                environment: 'cert',
                companyId: 1,
                certificateId: 3,
                privateKeyPem: 'NO_DEBE_USARSE',
                certificateBase64: 'NO_DEBE_USARSE',
                modulusBase64: 'NO_DEBE_USARSE',
                exponentBase64: 'NO_DEBE_USARSE',
            );
        } catch (\Throwable $e) {
            $this->fail(
                'Después del timeout del lock se intentó generar otro TOKEN sin revisar nuevamente el cache. Error: '
                . $e->getMessage()
            );
        }

        $this->assertSame(
            'TOKEN_APARECIDO_DURANTE_TIMEOUT',
            $result['token']
        );

        $this->assertSame(
            'cache',
            $result['source']
        );
    }
    public function test_no_genera_un_token_fuera_del_lock_si_el_timeout_ocurre_y_el_cache_sigue_vacio(): void
    {
        config()->set('dte.sii.transport.cache_store', 'file');

        config()->set('dte.sii.cert.soap.seed_url', '');
        config()->set('dte.sii.cert.soap.token_url', '');

        $cacheKey = 'sii:token:cert:company:1:certificate:3';

        $cache = Mockery::mock(CacheRepository::class);
        $lockStore = Mockery::mock(LockProvider::class);
        $lock = Mockery::mock(Lock::class);

        Cache::shouldReceive('store')
            ->once()
            ->with('file')
            ->andReturn($cache);

        $cache->shouldReceive('getStore')
            ->once()
            ->andReturn($lockStore);

        /*
        * Primera lectura:
        * no existe TOKEN.
        *
        * Segunda lectura después del timeout:
        * sigue sin existir.
        */
        $cache->shouldReceive('get')
            ->with($cacheKey)
            ->twice()
            ->andReturn(null);

        $lockStore->shouldReceive('lock')
            ->once()
            ->with(
                $cacheKey . ':lock',
                Mockery::type('int')
            )
            ->andReturn($lock);

        $lock->shouldReceive('block')
            ->once()
            ->andThrow(new LockTimeoutException());

        $service = app(SiiTokenProviderService::class);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'No fue posible obtener el lock para generar el TOKEN SII.'
        );

        $service->get(
            environment: 'cert',
            companyId: 1,
            certificateId: 3,
            privateKeyPem: 'NO_DEBE_USARSE',
            certificateBase64: 'NO_DEBE_USARSE',
            modulusBase64: 'NO_DEBE_USARSE',
            exponentBase64: 'NO_DEBE_USARSE',
        );
    }
    public function test_el_lock_para_generar_el_token_tiene_un_ttl_de_90_segundos(): void
    {
        config()->set('dte.sii.transport.cache_store', 'file');

        $cacheKey = 'sii:token:cert:company:1:certificate:3';

        $cache = Mockery::mock(CacheRepository::class);
        $lockStore = Mockery::mock(LockProvider::class);
        $lock = Mockery::mock(Lock::class);

        Cache::shouldReceive('store')
            ->once()
            ->with('file')
            ->andReturn($cache);

        $cache->shouldReceive('getStore')
            ->once()
            ->andReturn($lockStore);

        /*
        * No existe TOKEN inicialmente, por lo que el servicio
        * deberá intentar obtener el lock.
        */
        $cache->shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn(null);

        /*
        * Este test exige específicamente que el lock viva
        * 90 segundos.
        */
        $lockStore->shouldReceive('lock')
            ->once()
            ->with(
                $cacheKey . ':lock',
                90
            )
            ->andReturn($lock);

        /*
        * El worker puede seguir esperando solamente 30 segundos
        * para obtener el lock.
        *
        * Detenemos la ejecución aquí para no generar TOKEN
        * ni contactar al SII.
        */
        $lock->shouldReceive('block')
            ->once()
            ->with(
                30,
                Mockery::type('callable')
            )
            ->andThrow(
                new RuntimeException(
                    'DETENER_DESPUES_DE_VALIDAR_LOCK'
                )
            );

        $service = app(SiiTokenProviderService::class);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'DETENER_DESPUES_DE_VALIDAR_LOCK'
        );

        $service->get(
            environment: 'cert',
            companyId: 1,
            certificateId: 3,
            privateKeyPem: 'NO_DEBE_USARSE',
            certificateBase64: 'NO_DEBE_USARSE',
            modulusBase64: 'NO_DEBE_USARSE',
            exponentBase64: 'NO_DEBE_USARSE',
        );
    }
    public function test_cache_key_separa_ambiente_empresa_y_certificado(): void
    {
        $reflection = new ReflectionClass(
            SiiTokenProviderService::class
        );

        $service = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod(
            'cacheKey'
        );

        $method->setAccessible(true);

        $baseKey = $method->invoke(
            $service,
            ' CERT ',
            1,
            3
        );

        $this->assertSame(
            'sii:token:cert:company:1:certificate:3',
            $baseKey
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'prod',
                1,
                3
            ),
            'Ambientes distintos no pueden compartir la misma clave de token.'
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'cert',
                2,
                3
            ),
            'Empresas distintas no pueden compartir la misma clave de token.'
        );

        $this->assertNotSame(
            $baseKey,
            $method->invoke(
                $service,
                'cert',
                1,
                4
            ),
            'Certificados distintos no pueden compartir la misma clave de token.'
        );
    }
}
