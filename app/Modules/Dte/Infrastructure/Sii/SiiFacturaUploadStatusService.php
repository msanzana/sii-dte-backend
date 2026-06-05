<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use Illuminate\Support\Facades\Http;

class SiiFacturaUploadStatusService
{
    public function query(
        string $environment,
        string $token,
        string $companyRutBody,
        string $companyRutDv,
        string $trackId
    ):array
    {
        $url = $this->resolveQueryUrl($environment);
        $soap = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getEstUp xmlns:m="https://maullin.sii.cl/DTEWS/QueryEstUp.jws">
            <rutSender>{$companyRutBody}</rutSender>
            <dvSender>{$companyRutDv}</dvSender>
            <rutCompany>{$companyRutBody}</rutCompany>
            <dvCompany>{$companyRutDv}</dvCompany>
            <trackId>{$trackId}</trackId>
            <token>{$token}</token>
        </m:getEstUp>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => '',
        ])->withBody($soap, 'text/xml; charset=UTF-8')->post($url);

        if (!$response->successful()) {
            throw SiiUploadException::because(
                'La consulta QueryEstUp respondió con HTTP ' . $response->status()
            );
        }

        $body = (string) $response->body();

        return [
            'raw_body' => $body,
            'estado' => $this->extractXmlValue($body, 'ESTADO'),
            'glosa' => $this->extractXmlValue($body, 'GLOSA'),
            'error_code' => $this->extractXmlValue($body, 'ERR_CODE'),
            'glosa_err' => $this->extractXmlValue($body, 'GLOSA_ERR'),
            'num_atencion' => $this->extractXmlValue($body, 'NUM_ATENCION'),
        ];
    }

    private function resolveQueryUrl(string $environment): string
    {
        $url = (string) config("dte.sii.{$environment}.soap.query_est_up_url");

        if (trim($url) === '') {
            throw SiiUploadException::because(
                "No está configurada la URL QueryEstUp para el ambiente {$environment}."
            );
        }

        return $url;
    }

    private function extractXmlValue(string $xml, string $tag): ?string
    {
        if (preg_match('/<' . preg_quote($tag, '/') . '>\s*([^<]+)\s*<\/' . preg_quote($tag, '/') . '>/', $xml, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}


