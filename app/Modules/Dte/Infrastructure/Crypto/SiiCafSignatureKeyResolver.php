<?php

namespace App\Modules\Dte\Infrastructure\Crypto;

use App\Modules\Dte\Domain\Exceptions\InvalidCafException;

final class SiiCafSignatureKeyResolver
{
    public function resolve(string $siiKeyId): string
    {
        $normalizedKeyId = trim($siiKeyId);

        $key = config(
            "dte.sii.caf_signature_keys.{$normalizedKeyId}"
        );

        if (
            !is_string($key)
            || trim($key) === ''
        ) {
            throw InvalidCafException::because(
                "No existe una llave pública del SII configurada para IDK {$normalizedKeyId}."
            );
        }

        return trim($key);
    }
}