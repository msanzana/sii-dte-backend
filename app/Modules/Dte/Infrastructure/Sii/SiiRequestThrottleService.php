<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class SiiRequestThrottleService
{
    public function wait(string $environment):void
    {
        $minimumIntervalMs = max(
            0,
            (int) config(
                'dte.sii.transport.minimum_request_interval_ms',
                1500
            )
        );

        if ($minimumIntervalMs === 0) {
            return;
        }

        $storeName = (string) config(
            'dte.sii.transport.cache_store',
            'file'
        );

        /*
        |--------------------------------------------------------------------------
        | Repository de Laravel
        |--------------------------------------------------------------------------
        |
        | Lo usamos para leer/escribir valores del cache.
        |
        */
        $cache = Cache::store($storeName);

        /*
        |--------------------------------------------------------------------------
        | Store real
        |--------------------------------------------------------------------------
        |
        | getStore() nos entrega el motor de cache subyacente.
        | Ese motor es el que debe implementar LockProvider para poder usar
        | locks atómicos.
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

        $environment = strtolower(
            trim($environment)
        );

        $lockKey =
            "sii:transport:{$environment}:request-lock";

        $lastRequestKey =
            "sii:transport:{$environment}:last-request-at";


        try {
            $lockStore->lock($lockKey,10)
                        ->block(10,
                        function () use(
                            $cache,
                            $lastRequestKey,
                            $minimumIntervalMs
                        ):void{
                            $lastRequestAt = (float) $cache->get(
                                $lastRequestKey,
                                0.0
                            );

                            $now = microtime(true);

                            if($lastRequestAt >0)
                            {
                                $elapsedMs = ($now - $lastRequestAt) * 1000;
                                $remainingMs = $minimumIntervalMs - $elapsedMs;
                                if($remainingMs >0)
                                {
                                    usleep(
                                        (int) ceil(
                                            $remainingMs * 1000
                                        )
                                    );
                                }
                            }

                            $cache->put(
                                $lastRequestKey,
                                (string) microtime(true),
                                now()->addMinutes(10)
                            );
                        }
                    );
        } catch (LockTimeoutException) {
                        /*
            | Si excepcionalmente no se obtiene el lock,
            | respetamos igualmente el intervalo mínimo
            | para evitar una ráfaga de llamadas al SII.
            */

            usleep(
                $minimumIntervalMs * 1000
            );
        }

    }
} 