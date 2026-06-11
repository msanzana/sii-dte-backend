<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiBoletaApiConfigurationException;
use App\Modules\Dte\Domain\Exceptions\SiiBoletaSendException;
use Illuminate\Support\Facades\Http;

class SiiBoletaApiUploadService
{
    public function upload(
        string $environment,
        string $token,
        string $filename,
        string $xmlPayload
    ):array
    {
        $url = $this->resolveConfig($environment, 'sent_url');
        $httpMethod = strtoupper($this->resolveConfig($environment, 'send_http_method'));
        $mode = $this->resolveConfig($environment, 'send_node');
        $contentType = $this->resolveConfig($environment, 'send_content_type');
        $bodyField = $this->resolveConfig($environment, 'send_body_field');
        $headerName = $this->resolveConfig($environment, 'send_header_name');
        $headerPrefix = (string) config("dte.sii.{$environment}.token_header_prefix",'Bearer ');

        $request = Http::withHeaders([
            $headerName => $headerPrefix.$token,
            'Accept' => 'Application/json application/xml, text/plain',
        ]);

        $response = match ($mode) {
            'raw_xml' => $this->sendRawXml($request, $httpMethod, $url, $xmlPayload, $contentType),
            'json_xml' => $this->sendJsonXml($request, $httpMethod, $url, $bodyField, $xmlPayload, $filename),
            'json_base64_xml' => $this->sendJsonBase64Xml($request, $httpMethod, $url, $bodyField, $xmlPayload, $filename),
            'multipart_xml' => $this->sendMultipartXml($request,$httpMethod,$url,$bodyField,$xmlPayload,$filename),
            default => throw SiiBoletaSendException::because(
                "El modo REST de envío '{$mode}'"
            ),
        };

        if(!$response->successful())
        {
            throw SiiBoletaSendException::because(
                'La API REST de boleta respondió con HTTP '. $response->status(). ' al intentar enviar el documento.'
            );
        }

        $body = (string) $response->body();

        return [
            'http_status' => $response->status(),
            'raw_body' => $body,
            'track_id' => $this->extractFlexibleValue($body, [
                'trackid','track_id','trackId','trackingid','idEnvio'
            ]),
            'status_code' => $this->extractFlexibleValue($body, [
                'estado','status','codigo','code'
            ]),
            'status_message' => $this->extractFlexibleValue($body, [
                'glosa', 'message','descripcion', 'description','detail'
            ]),
        ];
    }

    private function sendRawXml($request, string $method, string $url, string $xmlPayload, string $contentType)
    {
        return $request->withBody($xmlPayload, $contentType)->send($method, $url);
    }

    private function sendJsonXml($request, string $method, string $url, string $bodyField, string $xmlPayload, string $filename)
    {
        return $request->send($method, $url, [
            'json' => [
                $bodyField => $xmlPayload,
                'filename' => $filename,
            ],
        ]);
    }

    private function sendJsonBase64Xml($request, string $method, string $url, string $bodyField, string $xmlPayload, string $filename)
    {
        return $request->send($method, $url, [
            'json'=> [
                $bodyField => base64_encode($xmlPayload),
                'filename' => $filename
            ],
        ]);
    }

    private function sendMultipartXml($request, string $method, string $url, string $bodyField, string $xmlPayload, string $filename)
    {
        return $request
                ->attach($bodyField,$xmlPayload, $filename,['Content-Type' => 'text/xml'])
                ->send($method, $url);
    }

    private function resolveConfig(string $environment, string $key): string
    {
        $value = (string) config("dte.sii.boleta.{$environment}.{$key}");

        if(trim($value) === '')
        {
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
            if(preg_match('/<'. preg_quote($key, '/') . '>\s*(.*?)\s*<\/'. preg_quote($key, '/') . '>/s', $body, $m))
            {
                return trim($m[1]);
            }
        }

        return null;
    }

    private function findRecursiveKey(array $data, string $targetKey): mixed
    {
        foreach($data as $key => $value)
        {
            if((string) $key=== $targetKey)
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
