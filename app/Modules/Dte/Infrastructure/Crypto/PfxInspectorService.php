<?php

namespace App\Modules\Dte\Infrastructure\Crypto;

use App\Modules\Dte\Domain\Exceptions\InvalidCertificateException;
use App\Modules\Dte\Domain\Exceptions\LegacyPfxUnsupportedException;

class PfxInspectorService
{
    public function inspect(string $pfxContents, string $password): array
    {
        $certStore = [];

        logger()->info('Intentando inspeccionar certificado PFX.', [
            'pfx_size_bytes' => strlen($pfxContents),
            'pfx_sha256' => hash('sha256', $pfxContents),
            'password_length' => strlen($password),
        ]);

        $ok = openssl_pkcs12_read($pfxContents, $certStore, $password);

        if (!$ok) {
            $opensslErrors = $this->collectOpenSslErrors();

            logger()->error('openssl_pkcs12_read falló al abrir el PFX.', [
                'pfx_size_bytes' => strlen($pfxContents),
                'openssl_errors' => $opensslErrors,
            ]);

            if ($this->hasUnsupportedAlgorithmError($opensslErrors)) {
                throw LegacyPfxUnsupportedException::because(
                    'El archivo PFX usa algoritmos antiguos no soportados por OpenSSL 3.'
                );
            }

            throw InvalidCertificateException::because(
                'No fue posible abrir el archivo PFX. Verifica que la contraseña sea correcta, que el archivo no esté dañado y que sea un PFX/P12 válido.'
            );
        }

        if (!isset($certStore['cert'])) {
            throw InvalidCertificateException::because(
                'El archivo PFX fue leído, pero no contiene certificado público.'
            );
        }

        if (!isset($certStore['pkey'])) {
            throw InvalidCertificateException::because(
                'El archivo PFX fue leído, pero no contiene clave privada.'
            );
        }

        $parsed = openssl_x509_parse($certStore['cert']);

        if ($parsed === false) {
            logger()->error('openssl_x509_parse falló al analizar el certificado.', [
                'openssl_errors' => $this->collectOpenSslErrors(),
            ]);

            throw InvalidCertificateException::because(
                'No fue posible analizar el certificado X509 contenido dentro del PFX.'
            );
        }

        $issuerName = null;

        if (array_key_exists('issuer', $parsed)) {
            $issuerValue = $parsed['issuer'];

            if (is_array($issuerValue)) {
                $issuerName = $this->flattenDn($issuerValue);
            } elseif (is_string($issuerValue)) {
                $issuerName = $issuerValue;
            }
        }

        return [
            'serial_number' => $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? null),
            'subject_name' => $parsed['name'] ?? null,
            'issuer_name' => $issuerName,
            'valid_from' => isset($parsed['validFrom_time_t'])
                ? date('Y-m-d H:i:s', (int) $parsed['validFrom_time_t'])
                : null,
            'valid_to' => isset($parsed['validTo_time_t'])
                ? date('Y-m-d H:i:s', (int) $parsed['validTo_time_t'])
                : null,
        ];
    }

    private function collectOpenSslErrors(): array
    {
        $errors = [];

        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }

        return $errors;
    }

    private function hasUnsupportedAlgorithmError(array $errors): bool
    {
        foreach ($errors as $error) {
            if (str_contains(strtolower($error), 'unsupported')) {
                return true;
            }
        }

        return false;
    }

    private function flattenDn(array $dn): string
    {
        $parts = [];

        foreach ($dn as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $parts[] = "{$key}={$subValue}";
                }

                continue;
            }

            $parts[] = "{$key}={$value}";
        }

        return implode(', ', $parts);
    }
}
