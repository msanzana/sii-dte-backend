<?php
namespace App\Modules\Dte\Infrastructure\Xml;

use App\Modules\Dte\Domain\Exceptions\InvalidSignatureXmlException;
use App\Modules\Dte\Infrastructure\Xml\XmlDsigIntegrityService;
use DOMDocument;
use DOMElement;
use DOMXPath;

class DteXmlSignatureService
{
    private const XMLDSIG_NS= 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N_ALGORITHM = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const SIGNATURE_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private const DIGEST_ALGORITHM = 'http://www.w3.org/2000/09/xmldsig#sha1';

    public function __construct(
        private readonly XmlDsigIntegrityService $integrityService
    ) {
    }
    public function signDte(
        string $xmlWithTed,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64
    ):array
    {
        $dom = new DOMDocument(
            '1.0',
            'ISO-8859-1'
        );

        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $loaded = @$dom->loadXML(
            $xmlWithTed,
            LIBXML_NONET
        );

        if(!$loaded){
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar el XML del DTE con TED para firmarlo.'
            );
        }

        $xpath = new DOMXPath($dom);

        $dteNode = $this->getRequiredElement(
            $xpath,
            "//*[local-name()='DTE']"
        );

        $documentoNode = $this->getRequiredElement(
            $xpath,
            "/*[local-name()='DTE']/*[local-name()='Documento']"
        );

        if($documentoNode->hasAttribute('ID') === false)
        {
            throw InvalidSignatureXmlException::because(
                'El nodeo documento no tiene atributo ID, por lo que no puede referenciarse en la firma.'
            );
        }

        $documentXmlId = trim($documentoNode->getAttribute('ID'));

        if($documentXmlId === '')
        {
            throw InvalidSignatureXmlException::because(
                'El atributo ID del nodo documento está vacío.'
            );
        }

        $existingTmstFirma = $xpath->query(
            "/*[local-name()='DTE']/*[local-name()='Documento']/*[local-name()='TmstFirma']"
        );

        if($existingTmstFirma !== false && $existingTmstFirma->length > 0)
        {
            throw InvalidSignatureXmlException::because(
                'El XML ya tiene un nodo TmstFirma; no corresponde firmarlo nuevamente.'
            );
        }

        $existingSignature = $xpath->query(
            "/*[local-name()='DTE']/*[local-name()='Signature' and namespace-uri()='".self::XMLDSIG_NS."']"
        );

        if($existingSignature !== false && $existingSignature->length > 0)
        {
            throw InvalidSignatureXmlException::because(
                'El XML ya contiene una firma XMLDSig; no corresponde firmarlo nuevamente.'
            );
        }

        $dteNamespace = $dteNode->namespaceURI;

        if($dteNamespace === null || trim($dteNamespace) === '')
        {
            throw InvalidSignatureXmlException::because(
                'El nodo DTE no tiene namespace válido.'
            );
        }

                /*
        * ============================================================
        * TmstFirma
        * ============================================================
        */

        $tmstFirma = now('America/Santiago')->format(
            'Y-m-d\TH:i:s'
        );

        $tmstFirmaNode =
            $dom->createElementNS(
                $dteNamespace,
                'TmstFirma'
            );

        $tmstFirmaNode->appendChild(
            $dom->createTextNode(
                $tmstFirma
            )
        );

        $documentoNode->appendChild(
            $tmstFirmaNode
        );


        /*
        * ============================================================
        * Crear Signature ANTES del C14N de Documento
        * ============================================================
        *
        * Signature es hermano de Documento, no hijo.
        *
        * Por lo tanto NO forma parte del contenido firmado de Documento,
        * pero permite estabilizar completamente el contexto de namespaces
        * del nodo DTE antes de calcular el DigestValue.
        */

        $signatureNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Signature'
            );


        /*
        * ============================================================
        * Canonicalizar Documento con su contexto XML DEFINITIVO
        * ============================================================
        */

        $canonicalDocumento =
            $documentoNode->C14N(
                false,
                false
            );

        if (
            $canonicalDocumento === false
            || $canonicalDocumento === ''
        ) {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar el nodo Documento para calcular el digest.'
            );
        }


        /*
        * ============================================================
        * DigestValue
        * ============================================================
        */

        $digestValue =
            base64_encode(
                sha1(
                    $canonicalDocumento,
                    true
                )
            );
        $signedInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'SignedInfo');

        $canonicalizationMethodNode = $dom->createElementNS(self::XMLDSIG_NS, 'CanonicalizationMethod');
        $canonicalizationMethodNode->setAttribute('Algorithm', self::C14N_ALGORITHM);

        $signatureMethodNode = $dom->createElementNS(self::XMLDSIG_NS, 'SignatureMethod');
        $signatureMethodNode->setAttribute('Algorithm', self::SIGNATURE_ALGORITHM);

        $referenceNode = $dom->createElementNS(self::XMLDSIG_NS, 'Reference');
        $referenceNode->setAttribute('URI', "#{$documentXmlId}");

        //Transforms
        /*
        $transformsNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Transforms');
        $transformNode = $dom->createElementNS(self::XMLDSIG_NS,'ds:Transform');
        $transformNode->setAttribute('Algorithm', self::C14N_ALGORITHM);
        $transformsNode->appendChild($transformNode);
        */
        $digestMethodNode = $dom->createElementNS(self::XMLDSIG_NS,'DigestMethod');
        $digestMethodNode->setAttribute('Algorithm', self::DIGEST_ALGORITHM);

        $digestValueNode = $dom->createElementNS(self::XMLDSIG_NS,'DigestValue');
        $digestValueNode->appendChild($dom->createTextNode($digestValue));

        //$referenceNode->appendChild($transformsNode);
        $referenceNode->appendChild($digestMethodNode);
        $referenceNode->appendChild($digestValueNode);

        $signedInfoNode->appendChild(
            $canonicalizationMethodNode
        );

        $signedInfoNode->appendChild(
            $signatureMethodNode
        );

        $signedInfoNode->appendChild(
            $referenceNode
        );

        $signatureNode->appendChild(
            $signedInfoNode
        );

        $dteNode->appendChild(
            $signatureNode
        );


        $canonicalSignedInfo = $signedInfoNode->C14N(false, false);

        if($canonicalSignedInfo === false || $canonicalSignedInfo === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible canonicalizar el nodo SignedInfo antes de firmarlo.'
            );
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if($privateKey === false)
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible cargar ña ññave privada del certificado para firmar el DTE.'
            );
        }

        $rawSignature = '';

        $signed = openssl_sign(
            $canonicalSignedInfo,
            $rawSignature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );
        // No es nesesario liverar la llave privada explícitamente, ya que desde la version 8.1 de PHP, el manejo de recursos de claves ha sido mejorado y se liberan automáticamente al finalizar su uso. Sin embargo, si se desea liberar explícitamente, se puede hacer con openssl_free_key($privateKey), aunque no es obligatorio.
        // openssl_free_key($privateKey);
        if(!$signed)
        {
            throw InvalidSignatureXmlException::because(
                'OpenSSL no pudo firmar SignedInfo del DTE.'
            );
        }

        $signatureValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignatureValue'
            );

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

        $keyInfoNode = $dom->createElementNS(self::XMLDSIG_NS,'KeyInfo');

        $keyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'KeyValue');
        $rsaKeyValueNode = $dom->createElementNS(self::XMLDSIG_NS,'RSAKeyValue');

        $modulusNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Modulus'
            );

        $modulusNode->appendChild(
            $dom->createTextNode(
                $this->wrapBase64(
                    $modulusBase64
                )
            )
        );

        $ExponentNode = $dom->createElementNS(self::XMLDSIG_NS, 'Exponent');
        $ExponentNode->appendChild($dom->createTextNode($exponentBase64));

        $rsaKeyValueNode->appendChild($modulusNode);
        $rsaKeyValueNode->appendChild($ExponentNode);
        $keyValueNode->appendChild($rsaKeyValueNode);

        $x509DataNode = $dom->createElementNS(self::XMLDSIG_NS,'X509Data');
        $x509CertificateNode = $dom->createElementNS(
                                    self::XMLDSIG_NS,
                                    'X509Certificate'
                                );
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

        $digestAntesDeSerializar =
            base64_encode(
                sha1(
                    $documentoNode->C14N(
                        false,
                        false
                    ),
                    true
                )
            );

        $signedXml = $dom->saveXML();

        if($signedXml === false || $signedXml === '')
        {
            throw InvalidSignatureXmlException::because(
                'No fue posible serializar el XML firmado del DTE.'
            );
        }
        /*
        * Verificamos el XML FINAL después de serializarlo.
        *
        * Esto comprueba:
        *
        * 1. DigestValue de Documento.
        * 2. SignatureValue de SignedInfo.
        */

        if (
            !hash_equals(
                $digestValue,
                $digestAntesDeSerializar
            )
        ) {
            throw InvalidSignatureXmlException::because(
                'El DigestValue del Documento cambió dentro del DOM antes de saveXML(). '
                . 'Original: '
                . $digestValue
                . '. Actual: '
                . $digestAntesDeSerializar
            );
        }

        $this->integrityService
            ->assertAllSignaturesValid(
                $signedXml
            );
        return [
            'signed_xml' => $signedXml,
            'tmst_firma' => $tmstFirma,
            'document_xml_id' => $documentXmlId,
        ];
    }

    private function wrapBase64(
        string $value,
        int $lineLength = 64
    ): string {
        /*
        * Primero eliminamos cualquier whitespace previo.
        */
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

        /*
        * Lo dividimos en líneas de 64 caracteres.
        *
        * No dejamos un salto adicional al final.
        */
        return rtrim(
            chunk_split(
                $normalized,
                $lineLength,
                "\n"
            ),
            "\r\n"
        );
    }
    private function getRequiredElement(DOMXPath $xpath, string $expression): DOMElement
    {
        $list = $xpath->query($expression);

        if($list === false || $list->length === 0)
        {
            throw InvalidSignatureXmlException::because(
                "No se encontró el nodo requerido para la firma: {$expression}"
            );
        }

        $node = $list->item(0);

        if(!$node instanceof DOMElement)
        {
            throw InvalidSignatureXmlException::because(
                "El nodo encontrado no es un elemento XML válido: {$expression}"
            );
        }

        return $node;
    }
}
