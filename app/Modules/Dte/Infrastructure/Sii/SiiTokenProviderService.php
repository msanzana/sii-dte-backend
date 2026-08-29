<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Throwable;

final class SiiTokenProviderService
{
    public function __construct(
        private readonly SiiSoapAuthenticationService $authenticationService,
    ){}
    public function get(
        string $environment,
        int $companyId,
        int $certificateId,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64,
    ):array
    {

        $storeName = (string) config(
            'dte.sii.transport.cache_store',
            'file'
        );

        /*
        |--------------------------------------------------------------------------
        | Repository de cache
        |--------------------------------------------------------------------------
        |
        | Lo utilizamos para get(), put(), forget(), etc.
        |
        */
        $cache = Cache::store($storeName);

        /*
        |--------------------------------------------------------------------------
        | Store real
        |--------------------------------------------------------------------------
        |
        | El store subyacente es el que debe implementar LockProvider
        | para poder crear locks atómicos.
        |
        */
        $lockStore = $cache->getStore();

        if (! $lockStore instanceof LockProvider) {
            throw new RuntimeException(
                sprintf(
                    'El cache store "%s" no soporta locks atómicos.',
                    $storeName
                )
            );
        }

        $cacheKey = $this->cacheKey(
            environment: $environment,
            companyId: $companyId,
            certificateId: $certificateId,
        );

        /*
        |--------------------------------------------------------------------------
        | Primero intentamos reutilizar un TOKEN existente
        |--------------------------------------------------------------------------
        */

        $cachedToken = $this->readCachedToken(
            $cache->get($cacheKey)
        );

        if ($cachedToken !== null) {

            return [
                'token' => $cachedToken,
                'source' => 'cache',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Si no existe TOKEN, bloqueamos para evitar que varios workers
        | soliciten uno nuevo simultáneamente.
        |--------------------------------------------------------------------------
        */

        try {
           return $lockStore->lock(
                $cacheKey. ':lock',
                30
           )
           ->block(
                30,
                function () use (
                    $cache,
                    $cacheKey,
                    $environment,
                    $privateKeyPem,
                    $certificateBase64,
                    $modulusBase64,
                    $exponentBase64,
                ): array{
                    $token = $this->authenticationService
                    ->authenticate(
                        environment: $environment,
                        privateKeyPem: $privateKeyPem,
                        certificateBase64: $certificateBase64,
                        modulusBase64: $modulusBase64,
                        exponentBase64: $exponentBase64,
                    );
                    $ttlMinutes = max(
                        1,
                        (int) config(
                            'dte.sii.transport.token_ttl_minutes',
                            55
                        )
                    );

                    $cache->put(
                        $cacheKey,
                        Crypt::encryptString($token),
                        now()->addMinutes($ttlMinutes)
                    );

                    return [
                        'token' => $token,
                        'source' => 'fresh',
                    ];
                }
           );
        } catch (LockTimeoutException) {
            $token = $this->authenticationService->authenticate(
                environment: $environment,
                privateKeyPem: $privateKeyPem,
                certificateBase64: $certificateBase64,
                modulusBase64: $modulusBase64,
                exponentBase64: $exponentBase64,
            );

            return [
                'token' => $token,
                'source' => 'fresh',
            ];
        }
    }
        
    public function forget
    (
        string $environment,
        int $companyId,
        int $certificateId,
    ):void {
        Cache::store(
            (string) config(
                'dte.sii.transport.cache_store',
                'file')
            )->forget(
                $this->cacheKey(
                    environment: $environment,
                    companyId: $companyId,
                    certificateId: $certificateId,
            )
        );
    }

    private function cacheKey(
        string $environment,
        int $companyId,
        int $certificateId,
    ):string {
        return printf(
            'sii:token:%s:company:$d:certificate:%d',
            $environment,
            $companyId,
            $certificateId,
        );
    }

    private function readCachedToken(
        mixed $encryptedToken
    ): ?string {
        if(!is_string($encryptedToken) || trim($encryptedToken) === '')
        {
            return null;
        }
        try {
            $token = Crypt::decryptString(
                $encryptedToken
            );
        } catch (Throwable) {
            return null;
        }

        $token = trim($token);

        return $token === ''
            ? null
            : $token;
    }
}