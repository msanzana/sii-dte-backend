<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use App\Modules\Dte\Infrastructure\Xml\XmlDsigIntegrityService;
use DOMDocument;
use DOMElement;
use DOMXPath;

class EnvioDteSignatureService
{
    private const XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N_ALGORITHM = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const SIGNATURE_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private const DIGEST_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#sha1';


    public function __construct(
        private readonly XmlDsigIntegrityService $integrityService
    ) {
    }
    public function SignSetDte(
        string $envioXml,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64
    ):string
    {
        $dom = new DOMDocument(
            '1.0',
            'ISO-8859-1'
        );

        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $loaded = @$dom->loadXML($envioXml,LIBXML_NONET);

        if (!$loaded)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el EnvioDTE para firmar el SetDTE.'
            );
        }

        $xpath = new DOMXPath($dom);

        $envioNode = $xpath->query("/*[local-name()='EnvioDTE']")->item(0);
        $setDteNode = $xpath->query("/*[local-name()='EnvioDTE']/*[local-name()='SetDTE']")->item(0);

        if(!$envioNode instanceof DOMElement || !$setDteNode instanceof DOMElement)
        {
            throw InvalidSignatureXmlException::because(
                'No se encontró EnvioDTE o SetDTE en el XML del envío'
            );
        }

        if (!$setDteNode->hasAttribute('ID'))
        {
            throw InvalidSignatureXmlException::because(
                'SetDTE no tiene atributo ID.'
            );
        }

        $setDteId= trim($setDteNode->getAttribute('ID'));

        if($setDteId === '')
        {
            throw InvalidSignatureXmlException::because(
                'El atributo ID de SetDTE está vacío.'
            );
        }

        $canonicalSetDte = $setDteNode->C14N(false,false);

        if($canonicalSetDte === false || $canonicalSetDte === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar SetDTE.'
            );
        }

        $digestValue =
        base64_encode(
            sha1(
                $canonicalSetDte,
                true
            )
        );

        //$signatureNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Signature');
        $signedInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:SignedInfo');

        $canonicalizationMethodNode = $dom->createElementNS(self::XMLDSIG_NS, 'ds:CanonicalizationMethod');
        $canonicalizationMethodNode->setAttribute('Algorithm', self::C14N_ALGORITHM);

        $signatureMethodNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:SignatureMethod');
        $signatureMethodNode->setAttribute('Algorithm', self::SIGNATURE_ALGORITHM);

        $referenceNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Reference');
        $referenceNode->setAttribute('URI', '#'.$setDteId);

        $transformsNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Transforms');
        $transformNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Transform');
        $transformNode->setAttribute('Algorithm', self::C14N_ALGORITHM);
        $transformsNode->appendChild($transformNode);

        $digestMethodNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:DigestMethod');
        $digestMethodNode->setAttribute('Algorithm', self::DIGEST_ALGORITHM);

        $digestValueNode = $dom->createElementNS(self::XMLDSIG_NS, 'ds:DigestValue');
        $digestValueNode->appendChild($dom->createTextNode($digestValue));

        $referenceNode->appendChild($transformsNode);
        $referenceNode->appendChild($digestMethodNode);
        $referenceNode->appendChild($digestValueNode);

        $signedInfoNode->appendChild($canonicalizationMethodNode);
        $signedInfoNode->appendChild($signatureMethodNode);
        $signedInfoNode->appendChild($referenceNode);


        /*
        * Crear Signature antes de canonicalizar SetDTE.
        *
        * Signature será hermano de SetDTE,
        * por lo que no forma parte de su contenido firmado.
        */
        $signatureNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'ds:Signature'
            );

        $envioNode->appendChild(
            $signatureNode
        );


        /*
        * Ahora canonicalizamos SetDTE dentro del
        * contexto definitivo de EnvioDTE.
        */

        $canonicalSignedInfo = $signedInfoNode->C14N(false,false);

        if($canonicalSignedInfo === false || $canonicalSignedInfo === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar SignedInfo de SetDTE.'
            );
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if($privateKey === false)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar la llave privada del certificado para firmar el SetDTE.'
            );
        }

        $rawSignature = '';
        $signed = openssl_sign(
            $canonicalSignedInfo,
            $rawSignature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );

        if(!$signed)
        {
            throw InvalidSignatureXmlException::because(
                'OpenSLL no pudo firmar SignedInfo del SetDTE.'
            );
        }

        $signatureValueNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:SignatureValue');
        $signatureValueNode->appendChild(
            $dom->createTextNode(
                $this->wrapBase64(
                    base64_encode(
                        $rawSignature
                    )
                )
            )
        );

        $signatureNode->appendChild($signatureValueNode);

        $keyInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:KeyInfo');

        $keyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:KeyValue');
        $rsaKeyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:RSAKeyValue');

        $modulusNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Modulus');
        $signatureValueNode->appendChild(
            $dom->createTextNode(
                $this->wrapBase64(
                    base64_encode(
                        $rawSignature
                    )
                )
            )
        );

        $exponentNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Exponent');
        $exponentNode->appendChild($dom->createTextNode($exponentBase64));

        $rsaKeyValueNode->appendChild($modulusNode);
        $rsaKeyValueNode->appendChild($exponentNode);
        $keyValueNode->appendChild($rsaKeyValueNode);

        $x509DataNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:X509Data');
        $x509CertificateNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:X509Certificate');
        $x509CertificateNode->appendChild(
            $dom->createTextNode(
                $this->wrapBase64(
                    $certificateBase64
                )
            )
        );
        $x509DataNode->appendChild($x509CertificateNode);

        $keyInfoNode->appendChild($keyValueNode);
        $keyInfoNode->appendChild($x509DataNode);
        $signatureNode->appendChild($keyInfoNode);

        $xml = $dom->saveXML();

        if (
            $xml === false
            || trim($xml) === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible serializar el EnvioDTE firmado.'
            );
        }

        /*
        * Aquí ya existen DOS firmas:
        *
        * 1. Firma del Documento DTE.
        * 2. Firma del SetDTE.
        *
        * Ambas deben sobrevivir a la serialización final.
        */
        
        
        $this->integrityService
            ->assertAllSignaturesValid(
                $xml
            );

        return $xml;
    }
    private function wrapBase64(
        string $value,
        int $lineLength = 64
    ): string {
        $normalized =
            preg_replace(
                '/\s+/',
                '',
                $value
            );

        if (
            $normalized === null
            || $normalized === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible normalizar un valor Base64 de la firma XML.'
            );
        }

        return rtrim(
            chunk_split(
                $normalized,
                $lineLength,
                "\n"
            ),
            "\r\n"
        );
    }
}
