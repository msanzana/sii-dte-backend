<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiConfigurationException;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiException;
use Illuminate\Support\Facades\Http;

class SiiBoletaApiAuthenticationService
{
    public function __construct(
        private readonly SiiSeedXmlSignerService $seedXmlSignerService,
        private readonly SiiRequestThrottleService $requestThrottleService,
    ) {
    }
    public function authenticate(
        string $environment,
        string $privateKeyPem,
        string $certificateBase64,
        string $modulusBase64,
        string $exponentBase64
    ): string
    {
        $seedUrl = $this->resolveConfig($environment, 'seed_url');
        $tokenUrl = $this->resolveConfig($environment, 'token_url');

        $this->requestThrottleService->wait($environment);

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

        $signedSeedXml = $this->seedXmlSignerService->sign(
            seed: $seed,
            privateKeyPem: $privateKeyPem,
            certificateBase64: $certificateBase64,
            modulusBase64: $modulusBase64,
            exponentBase64: $exponentBase64
        );

        $this->requestThrottleService->wait($environment);
        
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
        $json = json_decode($body, true);

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
