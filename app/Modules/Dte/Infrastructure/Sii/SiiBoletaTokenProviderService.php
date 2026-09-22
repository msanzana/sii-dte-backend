<?php

namespace App\Modules\Dte\Infrastructure\Sii;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\LockProvider;
use RuntimeException;
use Illuminate\Contracts\Cache\LockTimeoutException;
final class SiiBoletaTokenProviderService
{
    public function __construct(
        private readonly SiiBoletaApiAuthenticationService $authenticationService,
    ) {
    }

    /**
     * @return array{
     *     token: string,
     *     source: string
     * }
     */
    public function get(
        string $environment,
        int $companyId,
        int $certificateId,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64,
    ): array {
        $cacheKey = $this->cacheKey(
            environment: $environment,
            companyId: $companyId,
            certificateId: $certificateId,
        );

        $cachedToken = Cache::store('file')->get(
            $cacheKey
        );

        if (
            is_string($cachedToken)
            && trim($cachedToken) !== ''
        ) {
            return [
                'token' => trim($cachedToken),
                'source' => 'cache',
            ];
        }
        $lockKey = $cacheKey . ':lock';

        $lockStore = Cache::store('file')->getStore();

        if (!$lockStore instanceof LockProvider) {
            throw new RuntimeException(
                'El store de cache configurado para tokens SII de boleta no soporta locks.'
            );
        }
        try {
            return $lockStore
                ->lock(
                    $lockKey,
                    90
                )
                ->block(
                    30,
                    function () use (
                        $environment,
                        $privateKeyPem,
                        $certificateBase64,
                        $modulusBase64,
                        $exponentBase64,
                        $cacheKey
                    ): array {
                        $cachedTokenAfterLock = Cache::store('file')->get(
                            $cacheKey
                        );

                        if (
                            is_string($cachedTokenAfterLock)
                            && trim($cachedTokenAfterLock) !== ''
                        ) {
                            return [
                                'token' => trim($cachedTokenAfterLock),
                                'source' => 'cache',
                            ];
                        }

                        $token = $this->authenticationService->authenticate(
                            environment: $environment,
                            privateKeyPem: $privateKeyPem,
                            certificateBase64: $certificateBase64,
                            modulusBase64: $modulusBase64,
                            exponentBase64: $exponentBase64,
                        );

                        Cache::store('file')->put(
                            $cacheKey,
                            $token,
                            now()->addMinutes(
                                $this->cacheTtlMinutes()
                            )
                        );

                        return [
                            'token' => $token,
                            'source' => 'authentication',
                        ];
                    }
                );
        } catch (LockTimeoutException $exception) {
            $cachedTokenAfterTimeout = Cache::store('file')->get(
                $cacheKey
            );

            if (
                is_string($cachedTokenAfterTimeout)
                && trim($cachedTokenAfterTimeout) !== ''
            ) {
                return [
                    'token' => trim($cachedTokenAfterTimeout),
                    'source' => 'cache',
                ];
            }

            throw new RuntimeException(
                'No fue posible obtener el lock para generar el token SII de boleta y el cache continúa vacío.',
                0,
                $exception
            );
        }

    }
    private function cacheKey(
        string $environment,
        int $companyId,
        int $certificateId,
    ): string {
        return sprintf(
            'sii:boleta:token:%s:company:%d:certificate:%d',
            strtolower(trim($environment)),
            $companyId,
            $certificateId,
        );
    }
    private function cacheTtlMinutes(): int
    {
        $ttl = (int) config(
            'dte.sii.transport.token_cache_ttl_minutes',
            50
        );

        return $ttl > 0
            ? $ttl
            : 50;
    }
}
