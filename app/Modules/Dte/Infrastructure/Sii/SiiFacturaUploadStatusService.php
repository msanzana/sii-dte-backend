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
    ): array {
        $url = $this->resolveQueryUrl($environment);

        $rutXml = htmlspecialchars(
            $companyRutBody,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $dvXml = htmlspecialchars(
            $companyRutDv,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $trackIdXml = htmlspecialchars(
            $trackId,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $tokenXml = htmlspecialchars(
            $token,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );

        $soap = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope
    xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getEstUp xmlns:m="https://maullin.sii.cl/DTEWS/QueryEstUp.jws">
            <RutCompania xsi:type="xsd:string">{$rutXml}</RutCompania>
            <DvCompania xsi:type="xsd:string">{$dvXml}</DvCompania>
            <TrackId xsi:type="xsd:string">{$trackIdXml}</TrackId>
            <Token xsi:type="xsd:string">{$tokenXml}</Token>
        </m:getEstUp>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => '',
        ])
            ->withBody(
                $soap,
                'text/xml; charset=UTF-8'
            )
            ->post($url);

        $body = (string) $response->body();

        $decodedBody = html_entity_decode(
            $body,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return [
            'raw_body' => $body,
            'estado' => $this->extractXmlValue($decodedBody, 'ESTADO'),
            'glosa' => $this->extractXmlValue($decodedBody, 'GLOSA'),
            'error_code' => $this->extractXmlValue($decodedBody, 'ERR_CODE'),
            'glosa_err' => $this->extractXmlValue($decodedBody, 'GLOSA_ERR'),
            'num_atencion' => $this->extractXmlValue($decodedBody, 'NUM_ATENCION'),
        ];
    }

    private function resolveQueryUrl(
        string $environment
    ): string {
        $url = (string) config(
            "dte.sii.{$environment}.soap.query_est_up_url"
        );

        if (trim($url) === '') {
            throw SiiUploadException::because(
                "No está configurada la URL QueryEstUp para el ambiente {$environment}."
            );
        }

        return $url;
    }

    private function extractXmlValue(
        string $xml,
        string $tag
    ): ?string {
        if (
            preg_match(
                '/<'
                . preg_quote($tag, '/')
                . '>\s*([^<]+)\s*<\/'
                . preg_quote($tag, '/')
                . '>/',
                $xml,
                $matches
            )
        ) {
            return trim($matches[1]);
        }

        return null;
    }
}