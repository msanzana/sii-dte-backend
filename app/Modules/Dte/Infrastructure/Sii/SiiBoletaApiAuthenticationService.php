<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiConfigurationException;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiException;
use Illuminate\Support\Facades\Http;

class SiiBoletaApiAuthenticationService
{
    public function authenticate(
        string $environment,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64
    ): string
    {
        $seedUrl = $this->resolveConfig($environment, 'seed_url');
        $tokenUrl = $this->resolveConfig($environment, 'token_url');

        $seedResponse = Http::get($seedUrl);

        if(!$seedResponse->successful())
        {
            throw SiiBoletaApiException::because(
                'La API REST de boleta no respondió correctamente al solicitar semilla HTTP ' . $seedResponse->status()
            );
        }

        $seed = $this->extractFlexibleValue(
            (string) $seedResponse->body(),
            ['semilla','SEMILLA','seed','Seed']
        );

        if($seed === null || trim($seed) === '')
        {
            throw SiiBoletaApiException::because(
                'No fue posible extraer la semilla desde la respuesta REST de boleta'
            );
        }

        $signedSeedXml = $this->buildSignedSeedXml(
            $seed,
            $privateKeyPem,
            $certificateBase64,
            $modulusBase64
        );

        $tokenResponse = Http::withHeaders([
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Accept' => 'application/json, application/xml, text/plain'
        ])->withBody($signedSeedXml, 'application/xml; charset=UTF-8')->post($tokenUrl);

        if(!$tokenResponse->successful())
        {
            throw SiiBoletaApiException::because(
                'La API REST de boleta no respondió correctamente al solicitar token HTTP ' . $tokenResponse->status()
            );
        }

        $token = $this->extractFlexibleValue(
            (string) $tokenResponse->body(),
            ['token','TOKEN','access_token','jwt']
        );

        if($token === null || trim($token) === '')
        {
            throw SiiBoletaApiException::because(
                'No fue posible extraer el token desde la respuesta REST de boleta.'
            );
        }

        return trim($token);
    }

    private function buildSignedSeedXml(
        string $seed,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64
    ):string
    {
        $seedPayload = '<?xml version="1.0" encoding="ISO-8859-1"?><getToken><item><Semilla>' . $seed . '</Semilla></item></getToken>';

        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if($privateKey === false)
        {
            throw SiiBoletaApiException::because(
                'No fue posible cargar la llave private para firmar la semilla de boleta.'
            );
        }

        $signature = '';

        $signed = openssl_sign(
            $seedPayload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA1
        );

        if(!$signed)
        {
            throw SiiBoletaApiException::because(
                'OpenSSL no pudo firmar la semilla de la boleta.'
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
    private function resolveConfig(string $environment, string $key): string
    {
        $value = (string) config("dte.sii.boleta.{$environment}.{$key}");

        if (trim($value) === '') {
            throw SiiBoletaApiConfigurationException::missing($key, $environment);
        }

        return $value;
    }
    private function extractFlexibleValue(string $body, array $possibleKeys): ?string
    {
        $json = json_encode($body, true);

        if(is_array($json))
        {
            foreach($possibleKeys as $key)
            {
                $value = $this->findRecursiveKey($json, $key);

                if($value !== null && trim((string) $value) !== '')
                {
                    return trim((string) $value);
                }
            }
        }

        foreach($possibleKeys as $key)
        {
            if(preg_match('/<' . preg_quote($key,'/') . '>(.*?)<\/' . preg_quote($key,'/') . '>/i', $body, $m))
            {
                return trim($m[1]);
            }
        }

        return null;
    }

    private function findRecursiveKey(array $data, string $targetKey): mixed
    {
        foreach ($data as $key => $value) {
            if ((string) $key === $targetKey) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findRecursiveKey($value, $targetKey);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

}
