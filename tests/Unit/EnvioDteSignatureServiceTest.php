<?php

namespace Tests\Unit;

use App\Modules\Dte\Infrastructure\Xml\EnvioDteSignatureService;
use App\Modules\Dte\Infrastructure\Xml\XmlDsigIntegrityService;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class EnvioDteSignatureServiceTest extends TestCase
{
    public function test_puede_firmar_el_setdte_de_un_envio_boleta(): void
    {
        /*
         * ------------------------------------------------------------
         * Material criptográfico temporal para el test
         * ------------------------------------------------------------
         */
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($privateKey);

        $privateKeyPem = '';

        $exported = openssl_pkey_export(
            $privateKey,
            $privateKeyPem
        );

        $this->assertTrue($exported);

        $csr = openssl_csr_new(
            [
                'commonName' => 'test.local',
                'organizationName' => 'Test',
                'countryName' => 'CL',
            ],
            $privateKey
        );

        $this->assertNotFalse($csr);

        $certificate = openssl_csr_sign(
            $csr,
            null,
            $privateKey,
            1
        );

        $this->assertNotFalse($certificate);

        $certificatePem = '';

        $certificateExported = openssl_x509_export(
            $certificate,
            $certificatePem
        );

        $this->assertTrue($certificateExported);

        $certificateBase64 = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            $certificatePem
        );

        $this->assertIsString($certificateBase64);
        $this->assertNotSame('', $certificateBase64);

        $keyDetails = openssl_pkey_get_details(
            $privateKey
        );

        $this->assertIsArray($keyDetails);
        $this->assertArrayHasKey('rsa', $keyDetails);

        $modulusBase64 = base64_encode(
            $keyDetails['rsa']['n']
        );

        $exponentBase64 = base64_encode(
            $keyDetails['rsa']['e']
        );

        /*
         * ------------------------------------------------------------
         * Sobre mínimo de Boleta
         * ------------------------------------------------------------
         */
        $envioXml = <<<'XML'
<?xml version="1.0" encoding="ISO-8859-1"?>
<EnvioBOLETA xmlns="http://www.sii.cl/SiiDte" version="1.0">
    <SetDTE ID="SetBoleta_39">
        <Caratula version="1.0">
            <RutEmisor>77760724-3</RutEmisor>
        </Caratula>
    </SetDTE>
</EnvioBOLETA>
XML;

        /*
         * ------------------------------------------------------------
         * Firmar SetDTE
         * ------------------------------------------------------------
         */
        $service = new EnvioDteSignatureService(
            new XmlDsigIntegrityService()
        );

        $signedXml = $service->SignSetDte(
            envioXml: $envioXml,
            privateKeyPem: $privateKeyPem,
            certificateBase64: $certificateBase64,
            modulusBase64: $modulusBase64,
            exponentBase64: $exponentBase64,
        );

        /*
         * ------------------------------------------------------------
         * Inspeccionar resultado
         * ------------------------------------------------------------
         */
        $dom = new DOMDocument();

        $loaded = $dom->loadXML(
            $signedXml
        );

        $this->assertTrue($loaded);

        $xpath = new DOMXPath(
            $dom
        );

        /*
         * Debemos conservar EnvioBOLETA.
         */
        $envioNode = $xpath->query(
            "/*[local-name()='EnvioBOLETA']"
        );

        $this->assertNotFalse($envioNode);
        $this->assertSame(1, $envioNode->length);

        /*
         * El SetDTE debe conservar su ID.
         */
        $setDteNode = $xpath->query(
            "/*[local-name()='EnvioBOLETA']"
            . "/*[local-name()='SetDTE' and @ID='SetBoleta_39']"
        );

        $this->assertNotFalse($setDteNode);
        $this->assertSame(1, $setDteNode->length);

        /*
         * Signature debe ser hermano directo de SetDTE.
         */
        $signatureNode = $xpath->query(
            "/*[local-name()='EnvioBOLETA']"
            . "/*[local-name()='Signature'"
            . " and namespace-uri()='http://www.w3.org/2000/09/xmldsig#']"
        );

        $this->assertNotFalse($signatureNode);
        $this->assertSame(
            1,
            $signatureNode->length
        );

        /*
         * La firma del sobre debe referenciar exactamente
         * el SetDTE de la Boleta.
         */
        $referenceNode = $xpath->query(
            "/*[local-name()='EnvioBOLETA']"
            . "/*[local-name()='Signature']"
            . "/*[local-name()='SignedInfo']"
            . "/*[local-name()='Reference']"
        );

        $this->assertNotFalse($referenceNode);
        $this->assertSame(
            1,
            $referenceNode->length
        );

        $this->assertSame(
            '#SetBoleta_39',
            $referenceNode->item(0)->getAttribute('URI')
        );
    }
}
