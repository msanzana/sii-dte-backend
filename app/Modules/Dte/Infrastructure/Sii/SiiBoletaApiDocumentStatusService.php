<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiConfigurationException;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiException;
use Illuminate\Support\Facades\Http;

class SiiBoletaApiDocumentStatusService
{
    public function query(
        string $environment,
        string $token,
        array $payload
    ):array
    {
        $url = $this->resolveConfig($environment, 'document_status_url');
        $headerName = $this->resolveConfig($environment, 'token_header_name');
        $headerPrefix = (string) config("dto.sii.boleta.{$environment}.token.header.prefix", 'Bearer ');

        $response = Http::withHeaders([
            $headerName => $headerName . $token,
            'Accept' => 'application/json, application/xml, text/plain',
        ])->post($url, $payload);

        if(!$response->successful())
        {
            throw SiiBoletaApiException::because(
                "La API REST de boleta respondio con HTTP {$response->status()} al consultar el estado del documento"
            );
        }

        $body = (string) $response->body();

        return [
            'raw_body' => $body,
            'status_code' => $this->extractFlexibleValue($body, [
                'estado',
                'status',
                'codigo',
                'code',
                'estadoDocumento',
            ]),
            'status_message' => $this->extractFlexibleValue($body, [
                'glosa',
                'message',
                'descripcion',
                'description',
                'estado_glosa',
            ]),
        ];
    }

    private function resolveConfig(string $environment, string $key):string
    {
        $value = (string) config("dte.sii.{$environment}.{$key}");

        if(trim($value) === '')
        {
            throw SiiBoletaApiConfigurationException::missing($key, $environment);
        }

        return $value;
    }

    public function extractFlexibleValue(string $body, array $possibleKeys): ?string
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
            if(preg_match('/<'. preg_quote($key,'/'). '>\s*(.*?)\s*<\/'. preg_quote($key, '/'). '>/s', $body, $m))
            {
                return trim($m[1]);
            }
        }

        return null;
    }

    private function findRecursiveKey(array $data, string $targetKey):mixed
    {
        foreach($data as $key => $value)
        {
            if((string) $key === $targetKey)
            {
                return $value;
            }

            if(is_array($value))
            {
                $found = $this->findRecursiveKey($value, $targetKey);

                if($found !== null)
                {
                    return $found;
                }
            }
        }

        return null;
    }

}
