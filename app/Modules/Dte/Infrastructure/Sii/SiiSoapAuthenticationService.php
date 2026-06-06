<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiAuthenticationException;
use Illuminate\Support\Facades\Http;

class SiiSoapAuthenticationService
{
    public function authenticate(
        string $environment,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
    ):string
    {
        $seedUrl = $this->resolveSoapUrl($environment, 'seed_url');
        $tokenUrl = $this->resolveSoapUrl($environment, 'token_url');

        $seedXml = $this->requestSeed($seedUrl);
        $seed = $this->extractSeed($seedXml);

        $signedTokenRequestXml = $this->buildSignedSeedXml(
            $seed,
            $privateKeyPem,
            $certificateBase64,
            $modulusBase64
        );

        return $this->requestToken($tokenUrl, $signedTokenRequestXml);
    }

    private function requestSeed(string $seedUrl):string
    {
        // Implementar lógica para realizar la solicitud SOAP al seedUrl y obtener el XML de seed
        $soap = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getSeed xmlns:m="https://palena.sii.cl/DTEWS/CrSeed.jws"/>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => ''
        ])->withBody($soap, 'text/xml; charset=UTF-8')->post($seedUrl);

        if(!$response->successful())
        {
            throw SiiAuthenticationException::because(
                'El SII no respondió correctamente al solicitar la semilla. HTTP ' . $response->status()
            );
        }

        return (string) $response->body();
    }

    private function extractSeed(string $soapResponse): string
    {
        if(!preg_match('/<SEMILLA>([^<]+)<\/SEMILLA>/', $soapResponse, $m))
        {
            throw SiiAuthenticationException::because(
                'No fue posible extraer la semilla desde la respuesta SOAP del SII.'
            );
        }

        return trim($m[1]);
    }

    private function buildSignedSeedXml(
        string $seed,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64
    ): string
    {
        $seedPayload = '<?xml version="1.0" encoding="ISO-8859-1"?><getToken><item><Semilla>' . $seed . '</Semilla></item></getToken>';
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if ($privateKey === false)
        {
            throw SiiAuthenticationException::because(
                'No fue posible cargar la llave privada para firmar la semilla.'
            );
        }

        $signature = '';

        $signed = openssl_sign(
            $seedPayload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );

        if (!$signed) {
            throw SiiAuthenticationException::because(
                'OpenSll no pudo firmar la semilla del SII.'
            );
        }

        $signatureBase64 = base64_encode($signature);

        return <<<XML
        <?xml version="1.0" encoding="ISO-8859-1"?>
<getToken>
    <item>
        <Semilla>{$seed}</Semilla>
        <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
            <SignedInfo>
                <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />
                <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />
                <Reference URI="">
                    <Transforms>
                        <Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
                    </Transforms>
                    <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                    <DigestValue></DigestValue>
                </Reference>
            </SignedInfo>
            <SignatureValue>{$signatureBase64}</SignatureValue>
            <KeyInfo>
                <KeyValue>
                    <RSAKeyValue>
                        <Modulus>{$modulusBase64}</Modulus>
                    </RSAKeyValue>
                </KeyValue>
                <X509Data>
                    <X509Certificate>{$certificateBase64}</X509Certificate>
                </X509Data>
            </KeyInfo>
        </Signature>
    </item>
</getToken>
XML;
    }

    private function requestToken(string $tokenUrl, string $signedSeedXml): string
    {
        $escaped = htmlspecialchars($signedSeedXml, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $soap = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getToken xmlns:m="https://palena.sii.cl/DTEWS/GetTokenFromSeed.jws">
            <pszXml>{$escaped}</pszXml>
        </m:getToken>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;
        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => ''
        ])->withBody($soap, 'text/xml; charset=UTF-8')->post($tokenUrl);

        if (!$response->successful())
        {
           throw SiiAuthenticationException::because(
               'El SII no respondió correctamente al solicitar el token. HTTP ' . $response->status()
           );
        }

        $body = (string) $response->body();

        if(!preg_match('/<TOKEN>([^<]+)<\/TOKEN>/', $body, $m))
        {
            throw SiiAuthenticationException::because(
                'No fue posible extraer el TOKEN desde la respuesta del SII.'
            );
        }
        return trim($m[1]);
    }

    private function resolveSoapUrl(string $environment, string $key): string
    {
        $url = (string) config("dte.sii.{$environment}.soap.{$key}");

        if(trim($url) === '')
        {
            throw SiiAuthenticationException::because(
                "no esta configurada la URL SOAP {$key} para el ambiente {$environment}."
            );
        }
        return $url;
    }
}
