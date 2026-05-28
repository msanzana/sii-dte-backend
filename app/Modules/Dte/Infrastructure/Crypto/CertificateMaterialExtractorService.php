<?php
namespace App\Modules\Dte\Infrastructure\Crypto;

use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\Exceptions\InvalidCertificateException;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use Illuminate\Support\Facades\File;

class CertificateMaterialExtractorService
{
    public function __construct(
        private readonly SecretEncryptionService $secretEncryptionService,
    )
    {}

    public function extract(SiiCertificate $certificate): array
    {
        $absolutePath = storage_path($certificate->pfxPath());

        if (!File::exists($absolutePath))
        {
            throw new InvalidCertificateException(
                "No existe el archivo PFX almacenado en {$certificate->pfxPath()}."
            );
        }

        $pfxContents = File::get($absolutePath);

        if($pfxContents === false || trim($pfxContents) === '')
        {
            throw InvalidCertificateException::because(
                "No fue posible leer el contenido del archivo PFX almacenado."
            );
        }

        $password = $this->secretEncryptionService->decrypt(
            $certificate->pfxPasswordEncrypted()
        );

        $certStore = [];

        $ok = openssl_pkcs12_read(
            $pfxContents,
            $certStore,
            $password
        );

        if (!$ok)
        {
            throw InvalidCertificateException::because(
                'No fue posible abrir el PFX almacenado con la contraseña proporcionada.'
            );
        }

        if(
            !isset($certStore['cert']) ||
            !is_string($certStore['cert']) ||
            trim($certStore['cert']) === ''
        )
        {
            throw InvalidCertificateException::because(
                'El PFX almacenado no contiene un certificado X509 válido'
            );
        }

        if(
            !isset($certStore['pkey']) ||
            !is_string($certStore['pkey']) ||
            trim($certStore['pkey']) === ''
        )
        {
            throw InvalidCertificateException::because(
                'El PFX almacenado no contiene una llave privada válida.'
            );
        }

        $certificatePem = $certStore['cert'];
        $privateKeyPem = $certStore['pkey'];

        $publicKeyResource = openssl_pkey_get_public($certificatePem);

        if($publicKeyResource === false)
        {
            throw InvalidCertificateException::because(
                'No fue posible extraer la llave pública desde el certificado x509.'
            );
        }

        $details = openssl_pkey_get_details($publicKeyResource);

        if($details === false)
        {
            throw InvalidCertificateException::because(
                'No fue posible obtener los detalles de la llave pública del certificado.'
            );
        }

        if(
            !isset($details['rsa']) ||
            !is_array($details['rsa']) ||
            !isset($details['rsa']['n']) ||
            !isset($details['rsa']['e'])
        )
        {
            throw InvalidCertificateException::because(
                'La llave publica del certificado no expone un bloque RSA utilizable.'
            );
        }

        $modulus = base64_encode($details['rsa']['n']);
        $exponent = base64_encode($details['rsa']['e']);

        $certificateBase64 = $this->pemToBase64Body($certificatePem);

        return [
            'private_key_pem' => $privateKeyPem,
            'certificate_pem' => $certificatePem,
            'certificate_base64' => $certificateBase64,
            'modulus_base64' => $modulus,
            'exponent_base64' => $exponent,
        ];
    }

    private function pemToBase64Body(string $pem): string
    {
        $clean = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $pem);
        $clean = preg_replace('/-----END CERTIFICATE-----/', '', (string) $clean);
        $clean = preg_replace('/\s+/','',(string) $clean);

        if($clean === null || $clean === '')
        {
            throw InvalidCertificateException::because(
                'No fue posible obtener el cuerpo Base64 del certificado X509.'
            );
        }
        return $clean;
    }
}

