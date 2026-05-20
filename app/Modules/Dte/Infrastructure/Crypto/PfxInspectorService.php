<?php
namespace App\Modules\Dte\Infrastructure\Crypto;
use App\Modules\Dte\Domain\Exceptions\InvalidCertificateException;

class PfxInspectorService
{
    public function inspect(string $pfxContents, string $password): array
    {
        $certStore = [];

        $ok = openssl_pkcs12_read($pfxContents, $certStore, $password);
        if(!$ok)
        {
            throw invalidCertificateException::because(
                'No fue posible analizar el certificado X509 dentro del PFX'
            );
        }

        if(!isset($certStore['cert'])) {
            throw InvalidCertificateException::because(
                'No fue posible leer el archivo PFX. Verifica que la contraseña sea correcta y que el archivo no esté dañado'
            );
        }

        $parsed = openssl_x509_parse($certStore['cert']);

        if($parsed === false)
        {
            throw InvalidCertificateException::because(
                'No fue posible analizar el certificado X509 dentro del PFX.'
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
            'issuer_name'  => $issuerName,
            'valid_from' => isset($parsed['validFrom_time_t']) ? date('Y-m-d h:i:s', (int) $parsed['validFrom_time_t']) : null,
            'valid_to' => isset($parsed['validTo_time_t']) ? date('Y-m-d h:i:s', (int) $parsed['validTo_time_t']) : null,
        ];
    }

    private function flattenDn(array $dn): string
    {
        $parts = [];
        foreach ($dn as $key => $value) {
            if(is_array($value))
            {
                foreach($value as $subValue)
                {
                    $parts[] = "{$key}={$subValue}";
                }
                continue;
            }
            $parts[] = "{$key}={$value}";
        }
        return implode(', ', $parts);
     }
}
