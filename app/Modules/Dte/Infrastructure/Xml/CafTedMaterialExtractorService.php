<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Entities\SiiCaf;
use App\Modules\Dte\Domain\Exceptions\InvalidCafException;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;

class CafTedMaterialExtractorService
{
    public function __construct(
        private readonly SecretEncryptionService $secretEncryptionService,
    )
    {}

    public function extract(SiiCaf $caf):array
    {
        $absolutePath = storage_path($caf->cafXmlPath());

        if(!File::exists($absolutePath)) {
            throw InvalidCafException::because(
                "No existe el archivo XML del CAF en la ruta {$caf->cafXmlPath()}"
            );
        }

        $rawXml = File::get($absolutePath);

        if ($rawXml === false || trim($rawXml) === "") {
            throw InvalidCafException::because(
                "El archivo XML del CAF está vacío o no puede ser leido.",
            );
        }

        $cafXmlFragment = $this->extractExactCafFragment($rawXml);

        $encryptedPrivateKey = $caf->privateKeyPemEncrypted();

        if(trim($encryptedPrivateKey) === "") {
            throw InvalidCafException::because(
                'El CAF no contiene el material de la llave privada cifrado.'
            );
        }

        $decryptedPrivateMaterial = $this->secretEncryptionService->decrypt($encryptedPrivateKey);

        $privateKeyPem = $this->normalizePrivateKeyMaterial($decryptedPrivateMaterial);

        return [
            'caf_xml_fragment' => $cafXmlFragment,
            'private_key:pem' => $privateKeyPem,
        ];
    }

    private function extractExactCafFragment(string $rawXml): string
    {
        $matched = preg_match('/<CAF\b[^>]*>.*?<\/CAF>/s', $rawXml, $matches);

        if ($matched !== 1 || !isset($matches[0])) {
            throw InvalidCafException::because(
                'No fue posible extraer el fragmento <CAF>...</CAF> desde el XML original almacenado.'
            );
        }

        $cafFragment = trim($matches[0]);

        if ($cafFragment === '') {
            throw InvalidCafException::because(
                'El fragmento CAF extraído está vacío.'
            );
        }

        return $cafFragment;
    }

    private function normalizePrivateKeyMaterial(string $material): string
    {
        $clean = trim($material);

        if ($clean === '') {
            throw InvalidCafException::because(
                'La llave privada del CAF está vacía después de descifrarla.'
            );
        }

        if (str_starts_with($clean, '<RSASK')) {
            $dom = new DOMDocument();
            $loaded = @$dom->loadXML($clean);

            if (!$loaded) {
                throw InvalidCafException::because(
                    'No fue posible interpretar el material RSASK almacenado.'
                );
            }

            $xpath = new DOMXPath($dom);
            $nodeList = $xpath->query("//*[local-name()='RSASK']");

            if ($nodeList === false || $nodeList->length === 0) {
                throw InvalidCafException::because(
                    'No se encontró el nodo RSASK dentro del material almacenado.'
                );
            }

            $pem = trim($nodeList->item(0)->textContent);

            if ($pem === '') {
                throw InvalidCafException::because(
                    'El contenido PEM del RSASK está vacío.'
                );
            }

            return $pem;
        }

        if (
            str_contains($clean, 'BEGIN RSA PRIVATE KEY')
            || str_contains($clean, 'BEGIN PRIVATE KEY')
        ) {
            return $clean;
        }

        throw InvalidCafException::because(
            'El material privado del CAF no tiene un formato PEM reconocible.'
        );
    }
}
