<?php

namespace App\Modules\Dte\Infrastructure\Crypto;

use App\Modules\Dte\Domain\Exceptions\InvalidCafException;

final class SiiCafSignatureVerifier
{
    public function __construct(
        private readonly SiiCafSignatureKeyResolver $keyResolver
    ) {
    }

    public function verify(
        string $daXml,
        string $frmaValue,
        string $algorithm,
        string $siiKeyId
    ): bool {
        $normalizedAlgorithm = strtoupper(
            trim($algorithm)
        );

        if ($normalizedAlgorithm !== 'SHA1WITHRSA') {
            throw InvalidCafException::because(
                "Algoritmo FRMA no soportado: {$algorithm}."
            );
        }

        $publicKeyPem = $this->keyResolver->resolve(
            $siiKeyId
        );

        $signature = base64_decode(
            trim($frmaValue),
            true
        );

        if ($signature === false) {
            throw InvalidCafException::because(
                'La firma FRMA del CAF no contiene un Base64 válido.'
            );
        }

        $publicKey = openssl_pkey_get_public(
            $publicKeyPem
        );

        if ($publicKey === false) {
            throw InvalidCafException::because(
                "La llave pública del SII asociada al IDK {$siiKeyId} no es válida."
            );
        }

        /*
        * Primer intento:
        *
        * verificamos exactamente el DA tal como fue extraído
        * del CAF.
        */
        $result = openssl_verify(
            $daXml,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA1
        );

        if ($result === 1) {
            return true;
        }

        if ($result === -1) {
            throw InvalidCafException::because(
                'OpenSSL no pudo verificar la firma FRMA del CAF.'
            );
        }

        /*
        * Si la firma no coincide con la representación exacta,
        * hacemos un segundo intento eliminando únicamente el
        * whitespace existente ENTRE etiquetas.
        *
        * Ejemplo:
        *
        * <DA>
        *     <RE>...</RE>
        *     <TD>39</TD>
        * </DA>
        *
        * pasa a:
        *
        * <DA><RE>...</RE><TD>39</TD></DA>
        *
        * No modificamos el contenido interno de los nodos.
        */
        $normalizedDaXml = $this->normalizeDaXmlForSignature(
            $daXml
        );

        /*
        * Si la normalización no produjo ningún cambio,
        * no tiene sentido verificar exactamente lo mismo
        * una segunda vez.
        */
        if ($normalizedDaXml === $daXml) {
            return false;
        }

        $result = openssl_verify(
            $normalizedDaXml,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA1
        );

        if ($result === 1) {
            return true;
        }

        if ($result === 0) {
            return false;
        }

        throw InvalidCafException::because(
            'OpenSSL no pudo verificar la firma FRMA del CAF.'
        );
    }
    private function normalizeDaXmlForSignature(
        string $daXml
    ): string {
        /*
        * Eliminamos únicamente whitespace situado entre:
        *
        * >    <
        *
        * Esto incluye espacios, tabs, CR y LF.
        *
        * No hacemos trim() sobre los contenidos de los nodos
        * ni reconstruimos el XML mediante DOMDocument.
        */
        $normalized = preg_replace(
            '/>\s+</',
            '><',
            $daXml
        );

        if ($normalized === null) {
            throw InvalidCafException::because(
                'No fue posible normalizar el nodo DA para verificar FRMA.'
            );
        }

        return $normalized;
    }
}