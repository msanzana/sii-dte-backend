<?php

namespace Tests\Unit;

use App\Modules\Dte\Domain\Exceptions\InvalidCafException;
use App\Modules\Dte\Infrastructure\Crypto\SiiCafSignatureKeyResolver;
use Tests\TestCase;

final class SiiCafSignatureKeyResolverTest extends TestCase
{
    public function test_resuelve_la_llave_publica_del_sii_por_idk(): void
    {
        config([
            'dte.sii.caf_signature_keys' => [
                '100' => <<<'PEM'
-----BEGIN PUBLIC KEY-----
LLAVE-PUBLICA-SII-IDK-100
-----END PUBLIC KEY-----
PEM,
            ],
        ]);

        $resolver = new SiiCafSignatureKeyResolver();

        $result = $resolver->resolve('100');

        $this->assertSame(
            "-----BEGIN PUBLIC KEY-----\n"
            . "LLAVE-PUBLICA-SII-IDK-100\n"
            . "-----END PUBLIC KEY-----",
            $result
        );
    }

    public function test_rechaza_un_idk_del_sii_no_configurado(): void
    {
        config([
            'dte.sii.caf_signature_keys' => [
                '100' => <<<'PEM'
-----BEGIN PUBLIC KEY-----
LLAVE-PUBLICA-SII-IDK-100
-----END PUBLIC KEY-----
PEM,
            ],
        ]);

        $resolver = new SiiCafSignatureKeyResolver();

        $this->expectException(
            InvalidCafException::class
        );

        $this->expectExceptionMessage(
            'No existe una llave pública del SII configurada para IDK 999.'
        );

        $resolver->resolve('999');
    }
}