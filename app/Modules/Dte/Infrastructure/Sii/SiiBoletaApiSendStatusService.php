<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiConfigurationException;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendStatusException;
use Illuminate\Support\Facades\Http;

class SiiBoletaApiSendStatusService
{
    public function query(
        string $environment,
        string $token,
        string $trackId
    ):array
    {
        $url = $this->resolveConfig($environment, 'sent_status_url');
        $httpMethod = strtoupper($this->resolveConfig($environment, 'status_http_method'));
        $trackIdField = $this->resolveConfig($environment, 'status_track_id_field');
        $headerName = $this->resolveConfig($environment, 'token_header_name');
        $headerPrefix = (string) config("dte.sii.boleta.{$environment}.token_header_prefix", 'Bearer ');

        $response = Http::withHeaders([
            $headerName => $headerPrefix.$token,
            'Accept' => 'Aplication/json, application/xml, text/plain',
        ])->send($httpMethod, $url, [
            'json' => [
                $trackIdField => $trackId,
            ],
        ]);

        if(!$response->successful())
        {
            throw SiiBoletaSendStatusException::because(
                'La API REST de boleta respondió con HTTP '. $response->status() .' al consultar el estado del envio.'
            );
        }

        $body = (string) $response->body();

        return [
            'raw_body' => $body,
            'status_code' => $this->extractFlexibleValue($body, [
                'estado', 'status', 'codigo','code', 'estado_envio'
            ]),
            'status_message' => $this->extractFlexibleValue($body, [
                'glosa','message','descripcion','description','detail'
            ]),

        ];
    }

    private function resolveConfig(string $environment, string $key): string
    {
        $value = (string) config("dte.sii.boleta.{$environment}.{$key}");

        if(trim($value) == '')
        {
            throw SiiBoletaApiConfigurationException::missing($key, $environment);
        }
        return $value;
    }

    private function extractFlexibleValue(string $body, array $possibleKeys): ?string
    {
      $json = json_decode($body, true);

        if (is_array($json)) {
            foreach ($possibleKeys as $key) {
                $value = $this->findRecursiveKey($json, $key);

                if ($value !== null && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
        }

        foreach ($possibleKeys as $key) {
            if (preg_match('/<' . preg_quote($key, '/') . '>\s*(.*?)\s*<\/' . preg_quote($key, '/') . '>/s', $body, $m)) {
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
