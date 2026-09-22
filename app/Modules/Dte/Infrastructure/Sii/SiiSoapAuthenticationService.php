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
    public function __construct(
        private readonly SiiRequestThrottleService $requestThrottleService,
        private readonly SiiSeedXmlSignerService $seedXmlSignerService,
    ) {
    }
    /*
     * ==========================================================
     * XMLDSIG
     * ==========================================================
     */

    // private const XMLDSIG_NS =
    //     'http://www.w3.org/2000/09/xmldsig#';

    // private const C14N_ALGORITHM =
    //     'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    // private const SIGNATURE_ALGORITHM =
    //     'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

    // private const DIGEST_ALGORITHM =
    //     'http://www.w3.org/2000/09/xmldsig#sha1';

    // private const ENVELOPED_SIGNATURE_ALGORITHM =
    //     'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    // /*
    //  * ==========================================================
    //  * SOAP
    //  * ==========================================================
    //  */

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
            environment: $environment,
            seedUrl: $seedUrl,
        );

        /*
         * 3. Firmar semilla utilizando XMLDSig.
         */
        // $signedSeedXml =
        //     $this->buildSignedSeedXml(
        //         seed:
        //             $seed,

        //         privateKeyPem:
        //             $privateKeyPem,

        //         certificateBase64:
        //             $certificateBase64,

        //         modulusBase64:
        //             $modulusBase64,

        //         exponentBase64:
        //             $exponentBase64,
        //     );
$signedSeedXml =
        $this->seedXmlSignerService->sign(
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
            environment: $environment,
            tokenUrl: $tokenUrl,
            signedSeedXml: $signedSeedXml,
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
        string $environment,
        string $seedUrl
    ): string {
        $soap =
            $this->buildSeedSoapEnvelope(
                $seedUrl
            );

        $response =
            $this->sendSoapRequest(
                environment: $environment,
                url: $seedUrl,
                soap: $soap,
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
     * SOLICITUD TOKEN
     * ==========================================================
     */

    private function requestToken(
        string $environment,
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
                environment: $environment,
                url: $tokenUrl,
                soap: $soap,
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
        string $environment,
        string $url,
        string $soap
    ): Response {
        $this->requestThrottleService->wait($environment);
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
