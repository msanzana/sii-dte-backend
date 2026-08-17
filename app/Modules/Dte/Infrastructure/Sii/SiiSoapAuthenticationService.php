<?php

namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiAuthenticationException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class SiiSoapAuthenticationService
{
    /*
     * ==========================================================
     * XMLDSIG
     * ==========================================================
     */

    private const XMLDSIG_NS =
        'http://www.w3.org/2000/09/xmldsig#';

    private const C14N_ALGORITHM =
        'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    private const SIGNATURE_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    private const DIGEST_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#sha1';

    private const ENVELOPED_SIGNATURE_ALGORITHM =
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    /*
     * ==========================================================
     * SOAP
     * ==========================================================
     */

    private const SOAP_NS =
        'http://schemas.xmlsoap.org/soap/envelope/';

    private const SOAP_ENCODING_NS =
        'http://schemas.xmlsoap.org/soap/encoding/';

    private const XSI_NS =
        'http://www.w3.org/2001/XMLSchema-instance';

    private const XSD_NS =
        'http://www.w3.org/2001/XMLSchema';


    /*
     * ==========================================================
     * API PÚBLICA
     * ==========================================================
     */

    public function authenticate(
        string $environment,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64,
    ): string {
        /*
         * 1. Resolver endpoints SII.
         */
        [
            $seedUrl,
            $tokenUrl,
        ] = $this->resolveAuthenticationUrls(
            $environment
        );

        /*
         * 2. Solicitar semilla.
         */
        $seed = $this->requestSeed(
            $seedUrl
        );

        /*
         * 3. Firmar semilla utilizando XMLDSig.
         */
        $signedSeedXml =
            $this->buildSignedSeedXml(
                seed:
                    $seed,

                privateKeyPem:
                    $privateKeyPem,

                certificateBase64:
                    $certificateBase64,

                modulusBase64:
                    $modulusBase64,

                exponentBase64:
                    $exponentBase64,
            );

        /*
         * 4. Intercambiar semilla firmada por TOKEN.
         */
        return $this->requestToken(
            tokenUrl:
                $tokenUrl,

            signedSeedXml:
                $signedSeedXml,
        );
    }


    /*
     * ==========================================================
     * RESOLUCIÓN DE ENDPOINTS
     * ==========================================================
     */

    private function resolveAuthenticationUrls(
        string $environment
    ): array {
        $environment =
            strtolower(
                trim($environment)
            );

        if (
            !in_array(
                $environment,
                ['cert', 'prod'],
                true
            )
        ) {
            throw SiiAuthenticationException::because(
                "El ambiente SII '{$environment}' no es válido."
            );
        }

        $seedUrl = trim(
            (string) config(
                "dte.sii.{$environment}.soap.seed_url"
            )
        );

        $tokenUrl = trim(
            (string) config(
                "dte.sii.{$environment}.soap.token_url"
            )
        );

        if ($seedUrl === '') {
            throw SiiAuthenticationException::because(
                "No está configurada la URL CrSeed para el ambiente SII '{$environment}'."
            );
        }

        if ($tokenUrl === '') {
            throw SiiAuthenticationException::because(
                "No está configurada la URL GetTokenFromSeed para el ambiente SII '{$environment}'."
            );
        }

        return [
            $seedUrl,
            $tokenUrl,
        ];
    }


    /*
     * ==========================================================
     * SOLICITUD DE SEMILLA
     * ==========================================================
     */

    private function requestSeed(
        string $seedUrl
    ): string {
        $soap =
            $this->buildSeedSoapEnvelope(
                $seedUrl
            );

        $response =
            $this->sendSoapRequest(
                url:
                    $seedUrl,

                soap:
                    $soap,
            );

        /*
         * El WS devuelve getSeedReturn como xsd:string,
         * dentro del cual viene otro XML codificado.
         */
        $embeddedXml =
            $this->extractSoapReturnValue(
                soapResponse:
                    $response->body(),

                returnElementName:
                    'getSeedReturn',
            );

        return $this->extractSeedFromSiiResponse(
            $embeddedXml
        );
    }


    /*
     * ==========================================================
     * SOAP CrSeed
     * ==========================================================
     */

    private function buildSeedSoapEnvelope(
        string $seedUrl
    ): string {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope
    xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getSeed xmlns:m="{$seedUrl}"/>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
    }


    /*
     * ==========================================================
     * EXTRACCIÓN SEMILLA
     * ==========================================================
     */

    private function extractSeedFromSiiResponse(
        string $xml
    ): string {
        $dom =
            $this->loadXml(
                xml:
                    $xml,

                errorMessage:
                    'El XML retornado por CrSeed no es válido.'
            );

        $xpath =
            new DOMXPath(
                $dom
            );

        /*
         * Primero verificar ESTADO.
         */
        $status =
            $this->findNodeValue(
                xpath:
                    $xpath,

                localName:
                    'ESTADO',
            );

        if ($status !== '00') {
            $glosa =
                $this->findNodeValue(
                    xpath:
                        $xpath,

                    localName:
                        'GLOSA',
                );

            throw SiiAuthenticationException::because(
                'El SII rechazó la solicitud de semilla. '
                . 'Estado: '
                . ($status ?? 'desconocido')
                . '. Glosa: '
                . ($glosa ?? 'Sin glosa informada.')
            );
        }

        /*
         * El manual del SII contiene ejemplos tanto
         * con SEMILLA como con SEED.
         *
         * El servicio operativo habitualmente utiliza
         * SEMILLA, pero soportamos ambos.
         */
        $seed =
            $this->findNodeValue(
                xpath:
                    $xpath,

                localName:
                    'SEMILLA',
            );

        if ($seed === null) {
            $seed =
                $this->findNodeValue(
                    xpath:
                        $xpath,

                    localName:
                        'SEED',
                );
        }

        if (
            $seed === null
            || trim($seed) === ''
        ) {
            throw SiiAuthenticationException::because(
                'La respuesta de CrSeed no contiene una semilla válida.'
            );
        }

        return trim(
            $seed
        );
    }


    /*
     * ==========================================================
     * CONSTRUCCIÓN SEMILLA FIRMADA
     * ==========================================================
     */

    private function buildSignedSeedXml(
        string $seed,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64,
    ): string {
        /*
         * ------------------------------------------------------
         * Normalización del material criptográfico.
         * ------------------------------------------------------
         */

        $certificateBase64 =
            $this->normalizeBase64(
                $certificateBase64
            );

        $modulusBase64 =
            $this->normalizeBase64(
                $modulusBase64
            );

        $exponentBase64 =
            $this->normalizeBase64(
                $exponentBase64
            );

        if ($certificateBase64 === '') {
            throw SiiAuthenticationException::because(
                'El certificado X509 para autenticación SII está vacío.'
            );
        }

        if ($modulusBase64 === '') {
            throw SiiAuthenticationException::because(
                'El módulo RSA para autenticación SII está vacío.'
            );
        }

        if ($exponentBase64 === '') {
            throw SiiAuthenticationException::because(
                'El exponente RSA para autenticación SII está vacío.'
            );
        }

        /*
         * ------------------------------------------------------
         * Crear XML base.
         *
         * <getToken>
         *     <item>
         *         <Semilla>...</Semilla>
         *     </item>
         * </getToken>
         * ------------------------------------------------------
         */

        $dom =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $dom->preserveWhiteSpace =
            true;

        $dom->formatOutput =
            false;

        $getTokenNode =
            $dom->createElement(
                'getToken'
            );

        $dom->appendChild(
            $getTokenNode
        );

        $itemNode =
            $dom->createElement(
                'item'
            );

        $getTokenNode->appendChild(
            $itemNode
        );

        $seedNode =
            $dom->createElement(
                'Semilla'
            );

        $seedNode->appendChild(
            $dom->createTextNode(
                trim($seed)
            )
        );

        $itemNode->appendChild(
            $seedNode
        );

        /*
         * ------------------------------------------------------
         * DIGEST
         *
         * Reference URI="" referencia al documento actual.
         *
         * Como Signature todavía no existe, canonicalizar
         * getToken en este punto equivale al resultado del
         * transform enveloped-signature.
         * ------------------------------------------------------
         */

        $canonicalGetToken =
            $getTokenNode->C14N(
                false,
                false
            );

        if (
            $canonicalGetToken === false
            || $canonicalGetToken === ''
        ) {
            throw SiiAuthenticationException::because(
                'No fue posible canonicalizar getToken para calcular DigestValue.'
            );
        }

        $digestValue =
            base64_encode(
                sha1(
                    $canonicalGetToken,
                    true
                )
            );

        /*
         * ------------------------------------------------------
         * Signature
         * ------------------------------------------------------
         */

        $signatureNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Signature'
            );

        /*
         * ------------------------------------------------------
         * SignedInfo
         * ------------------------------------------------------
         */

        $signedInfoNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignedInfo'
            );

        /*
         * CanonicalizationMethod
         */

        $canonicalizationMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'CanonicalizationMethod'
            );

        $canonicalizationMethodNode
            ->setAttribute(
                'Algorithm',
                self::C14N_ALGORITHM
            );

        /*
         * SignatureMethod
         */

        $signatureMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignatureMethod'
            );

        $signatureMethodNode
            ->setAttribute(
                'Algorithm',
                self::SIGNATURE_ALGORITHM
            );

        /*
         * Reference
         */

        $referenceNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Reference'
            );

        $referenceNode->setAttribute(
            'URI',
            ''
        );

        /*
         * Transforms
         */

        $transformsNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Transforms'
            );

        $transformNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Transform'
            );

        $transformNode->setAttribute(
            'Algorithm',
            self::ENVELOPED_SIGNATURE_ALGORITHM
        );

        $transformsNode->appendChild(
            $transformNode
        );

        /*
         * DigestMethod
         */

        $digestMethodNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'DigestMethod'
            );

        $digestMethodNode->setAttribute(
            'Algorithm',
            self::DIGEST_ALGORITHM
        );

        /*
         * DigestValue
         */

        $digestValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'DigestValue'
            );

        $digestValueNode->appendChild(
            $dom->createTextNode(
                $digestValue
            )
        );

        /*
         * Reference completo.
         */

        $referenceNode->appendChild(
            $transformsNode
        );

        $referenceNode->appendChild(
            $digestMethodNode
        );

        $referenceNode->appendChild(
            $digestValueNode
        );

        /*
         * SignedInfo completo.
         */

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

        /*
         * ------------------------------------------------------
         * IMPORTANTE
         *
         * Signature es hermano de item, NO hijo de item.
         *
         * <getToken>
         *     <item>...</item>
         *     <Signature>...</Signature>
         * </getToken>
         *
         * También debe estar conectado al DOM antes de
         * canonicalizar SignedInfo.
         * ------------------------------------------------------
         */

        $getTokenNode->appendChild(
            $signatureNode
        );

        /*
         * ------------------------------------------------------
         * Canonicalizar SignedInfo.
         * ------------------------------------------------------
         */

        $canonicalSignedInfo =
            $signedInfoNode->C14N(
                false,
                false
            );

        if (
            $canonicalSignedInfo === false
            || $canonicalSignedInfo === ''
        ) {
            throw SiiAuthenticationException::because(
                'No fue posible canonicalizar SignedInfo de la semilla.'
            );
        }

        /*
         * ------------------------------------------------------
         * Cargar llave privada.
         * ------------------------------------------------------
         */

        $privateKey =
            openssl_pkey_get_private(
                $privateKeyPem
            );

        if ($privateKey === false) {
            throw SiiAuthenticationException::because(
                'No fue posible cargar la llave privada para firmar la semilla.'
            );
        }

        /*
         * ------------------------------------------------------
         * Firmar SignedInfo.
         * ------------------------------------------------------
         */

        $rawSignature =
            '';

        $signed =
            openssl_sign(
                $canonicalSignedInfo,
                $rawSignature,
                $privateKey,
                OPENSSL_ALGO_SHA1
            );

        if (!$signed) {
            throw SiiAuthenticationException::because(
                'OpenSSL no pudo firmar SignedInfo de la semilla.'
            );
        }

        /*
         * ------------------------------------------------------
         * SignatureValue
         * ------------------------------------------------------
         */

        $signatureValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'SignatureValue'
            );

        $signatureValueNode->appendChild(
            $dom->createTextNode(
                base64_encode(
                    $rawSignature
                )
            )
        );

        $signatureNode->appendChild(
            $signatureValueNode
        );

        /*
         * ------------------------------------------------------
         * KeyInfo
         * ------------------------------------------------------
         */

        $keyInfoNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'KeyInfo'
            );

        /*
         * KeyValue
         */

        $keyValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'KeyValue'
            );

        $rsaKeyValueNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'RSAKeyValue'
            );

        /*
         * Modulus
         */

        $modulusNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Modulus'
            );

        $modulusNode->appendChild(
            $dom->createTextNode(
                $modulusBase64
            )
        );

        /*
         * Exponent
         */

        $exponentNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'Exponent'
            );

        $exponentNode->appendChild(
            $dom->createTextNode(
                $exponentBase64
            )
        );

        $rsaKeyValueNode->appendChild(
            $modulusNode
        );

        $rsaKeyValueNode->appendChild(
            $exponentNode
        );

        $keyValueNode->appendChild(
            $rsaKeyValueNode
        );

        /*
         * X509Data
         */

        $x509DataNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'X509Data'
            );

        $x509CertificateNode =
            $dom->createElementNS(
                self::XMLDSIG_NS,
                'X509Certificate'
            );

        $x509CertificateNode->appendChild(
            $dom->createTextNode(
                $certificateBase64
            )
        );

        $x509DataNode->appendChild(
            $x509CertificateNode
        );

        /*
         * KeyInfo completo.
         */

        $keyInfoNode->appendChild(
            $keyValueNode
        );

        $keyInfoNode->appendChild(
            $x509DataNode
        );

        $signatureNode->appendChild(
            $keyInfoNode
        );

        /*
         * ------------------------------------------------------
         * Serializar.
         * ------------------------------------------------------
         */

        $xml =
            $dom->saveXML();

        if (
            $xml === false
            || trim($xml) === ''
        ) {
            throw SiiAuthenticationException::because(
                'No fue posible serializar la semilla firmada.'
            );
        }

        return $xml;
    }


    /*
     * ==========================================================
     * SOLICITUD TOKEN
     * ==========================================================
     */

    private function requestToken(
        string $tokenUrl,
        string $signedSeedXml
    ): string {
        $soap =
            $this->buildTokenSoapEnvelope(
                tokenUrl:
                    $tokenUrl,

                signedSeedXml:
                    $signedSeedXml,
            );

        $response =
            $this->sendSoapRequest(
                url:
                    $tokenUrl,

                soap:
                    $soap,
            );

        /*
         * getTokenReturn también es xsd:string.
         */
        $embeddedXml =
            $this->extractSoapReturnValue(
                soapResponse:
                    $response->body(),

                returnElementName:
                    'getTokenReturn',
            );

        return $this->extractTokenFromSiiResponse(
            $embeddedXml
        );
    }


    /*
     * ==========================================================
     * SOAP GetTokenFromSeed
     * ==========================================================
     */

    private function buildTokenSoapEnvelope(
        string $tokenUrl,
        string $signedSeedXml
    ): string {
        /*
         * pszXml es un STRING dentro de otro XML.
         *
         * Por eso el XML firmado debe escapar:
         *
         * <  -> &lt;
         * >  -> &gt;
         * &  -> &amp;
         */
        $escapedXml =
            htmlspecialchars(
                $signedSeedXml,
                ENT_XML1 | ENT_QUOTES,
                'UTF-8'
            );

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope
    xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getToken xmlns:m="{$tokenUrl}">
            <pszXml xsi:type="xsd:string">{$escapedXml}</pszXml>
        </m:getToken>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
    }


    /*
     * ==========================================================
     * EXTRAER TOKEN
     * ==========================================================
     */

    private function extractTokenFromSiiResponse(
        string $xml
    ): string {
        $dom =
            $this->loadXml(
                xml:
                    $xml,

                errorMessage:
                    'El XML retornado por GetTokenFromSeed no es válido.'
            );

        $xpath =
            new DOMXPath(
                $dom
            );

        $status =
            $this->findNodeValue(
                xpath:
                    $xpath,

                localName:
                    'ESTADO',
            );

        $glosa =
            $this->findNodeValue(
                xpath:
                    $xpath,

                localName:
                    'GLOSA',
            );

        if ($status !== '00') {
            throw SiiAuthenticationException::because(
                'El SII rechazó la generación del TOKEN. '
                . 'Estado: '
                . ($status ?? 'desconocido')
                . '. Glosa: '
                . ($glosa ?? 'Sin glosa informada.')
            );
        }

        $token =
            $this->findNodeValue(
                xpath:
                    $xpath,

                localName:
                    'TOKEN',
            );

        if (
            $token === null
            || trim($token) === ''
        ) {
            throw SiiAuthenticationException::because(
                'El SII respondió con estado 00 pero no entregó un TOKEN.'
            );
        }

        return trim(
            $token
        );
    }


    /*
     * ==========================================================
     * CLIENTE HTTP SOAP
     * ==========================================================
     */

    private function sendSoapRequest(
        string $url,
        string $soap
    ): Response {
        try {
            $response =
                Http::timeout(30)
                    ->connectTimeout(15)
                    ->withHeaders([
                        'Content-Type' =>
                            'text/xml; charset=UTF-8',

                        'SOAPAction' =>
                            '',
                    ])
                    ->withBody(
                        $soap,
                        'text/xml; charset=UTF-8'
                    )
                    ->post(
                        $url
                    );
        } catch (\Throwable $e) {
            throw SiiAuthenticationException::because(
                'No fue posible conectar con el Web Service de autenticación del SII: '
                . $e->getMessage()
            );
        }

        if (!$response->successful()) {
            throw SiiAuthenticationException::because(
                'El Web Service de autenticación del SII respondió HTTP '
                . $response->status()
                . '.'
            );
        }

        $body =
            (string) $response->body();

        if (trim($body) === '') {
            throw SiiAuthenticationException::because(
                'El Web Service de autenticación del SII respondió sin contenido.'
            );
        }

        return $response;
    }


    /*
     * ==========================================================
     * EXTRAER STRING EMBEBIDO EN SOAP
     * ==========================================================
     */

    private function extractSoapReturnValue(
        string $soapResponse,
        string $returnElementName
    ): string {
        $dom =
            $this->loadXml(
                xml:
                    $soapResponse,

                errorMessage:
                    'La respuesta SOAP del SII no contiene XML válido.'
            );

        $xpath =
            new DOMXPath(
                $dom
            );

        /*
         * Primero detectar SOAP Fault.
         */
        $faultNode =
            $xpath
                ->query(
                    "//*[local-name()='Fault']"
                )
                ->item(0);

        if ($faultNode instanceof DOMNode) {
            throw SiiAuthenticationException::because(
                'El SII respondió con SOAP Fault: '
                . trim(
                    $faultNode->textContent
                )
            );
        }

        /*
         * Buscar getSeedReturn / getTokenReturn.
         */
        $node =
            $xpath
                ->query(
                    "//*[local-name()='{$returnElementName}']"
                )
                ->item(0);

        if (!$node instanceof DOMNode) {
            throw SiiAuthenticationException::because(
                "La respuesta SOAP del SII no contiene {$returnElementName}."
            );
        }

        /*
         * DOM textContent ya transforma:
         *
         * &lt; → <
         * &gt; → >
         * &amp; → &
         */
        $value =
            trim(
                $node->textContent
            );

        if ($value === '') {
            throw SiiAuthenticationException::because(
                "El elemento {$returnElementName} retornado por el SII está vacío."
            );
        }

        return $value;
    }


    /*
     * ==========================================================
     * XML HELPER
     * ==========================================================
     */

    private function loadXml(
        string $xml,
        string $errorMessage
    ): DOMDocument {
        $dom =
            new DOMDocument();

        $dom->preserveWhiteSpace =
            true;

        $dom->formatOutput =
            false;

        $previous =
            libxml_use_internal_errors(
                true
            );

        libxml_clear_errors();

        $loaded =
            $dom->loadXML(
                $xml,
                LIBXML_NONET
            );

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previous
        );

        if (!$loaded) {
            throw SiiAuthenticationException::because(
                $errorMessage
            );
        }

        return $dom;
    }


    /*
     * ==========================================================
     * BUSCAR ELEMENTO POR local-name()
     * ==========================================================
     */

    private function findNodeValue(
        DOMXPath $xpath,
        string $localName
    ): ?string {
        $node =
            $xpath
                ->query(
                    "//*[local-name()='{$localName}']"
                )
                ->item(0);

        if (!$node instanceof DOMNode) {
            return null;
        }

        return trim(
            $node->textContent
        );
    }


    /*
     * ==========================================================
     * NORMALIZAR BASE64
     * ==========================================================
     */

    private function normalizeBase64(
        string $value
    ): string {
        $normalized =
            preg_replace(
                '/\s+/',
                '',
                trim($value)
            );

        return $normalized ?? '';
    }
}
