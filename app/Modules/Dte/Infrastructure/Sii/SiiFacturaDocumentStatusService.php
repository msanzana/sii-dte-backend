<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiDocumentStatusException;
use Illuminate\Support\Facades\Http;

class SiiFacturaDocumentStatusService
{
    public function query(
        string $environment,
        string $consultantRutBody,
        string $consultantRutDv,
        string $companyRutBody,
        string $companyRutDv,
        string $receiverRutBody,
        string $receiverRutDv,
        string $dteType,
        string $folio,
        string $issueDate,
        string $amount,
        string $token
    ):array
    {
        $url = $this->resolveUrl($environment);

        $soap =<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <SOAP-ENV:Body>
        <m:getEstDte xmlns:m="https://maullin.sii.cl/DTEWS/QueryEstDte.jws">
            <RutConsultante>{$consultantRutBody}</RutConsultante>
            <DvConsultante>{$consultantRutDv}</DvConsultante>
            <RutCompania>{$companyRutBody}</RutCompania>
            <DvCompania>{$companyRutDv}</DvCompania>
            <RutReceptor>{$receiverRutBody}</RutReceptor>
            <DvReceptor>{$receiverRutDv}</DvReceptor>
            <TipoDte>{$dteType}</TipoDte>
            <FolioDte>{$folio}</FolioDte>
            <FechaEmisionDte>{$issueDate}</FechaEmisionDte>
            <MontoDte>{$amount}</MontoDte>
            <Token>{$token}</Token>
        </m:getEstDte>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => ''
        ])->withBody($soap, 'text/xml; charset=UTF-8')->post($url);

        if(!$response->successful())
        {
            throw SiiDocumentStatusException::because(
                'QueryEstDte respondi+o con HTTP '. $response->status()
            );
        }

        $body = (string) $response->body();

        $encodedReturn = $this->extractTagValue($body, 'getEstDteReturn');

        if($encodedReturn === null)
        {
            throw SiiDocumentStatusException::because(
                'No fue posible extraer el getEstDteReturn desde la respuesta SOAP del QueryEstDte'
            );
        }

        $decodedXml = html_entity_decode($encodedReturn, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return [
            'raw_body' => $decodedXml,
            'estado' => $this->extractTagValue($decodedXml, 'ESTADO'),
            'glosa' => $this->extractTagValue($decodedXml, 'GLOSA')
                        ?? $this->extractTagValue($decodedXml, 'GLOSA_ESTADO'),
            'err_code' => $this->extractTagValue($decodedXml, 'ERR_CODE'),
            'glosa_err' => $this->extractTagValue($decodedXml, 'GLOSA_ERR'),
            'num_atencion' => $this->extractTagValue($decodedXml, 'NUM_ATENCION'),

        ];
    }

    private function resolveUrl(string $environment): string
    {
        $url = (string) config("dte.sii.{$environment}.soap.query_est_dte_url");

        if(trim($url) === '')
        {
            throw SiiDocumentStatusException::because(
                "No está configurada la URL QUeryEstDte para el ambiente {$environment}."
            );
        }
        return $url;
    }

    protected function extractTagValue(string $xml, string $tag): ?string
    {
        $escapedTag = preg_quote($tag, '/');

        $pattern = '/<(?:[\w.-]+:)?'
            . $escapedTag
            . '\b[^>]*>\s*(.*?)\s*<\/(?:[\w.-]+:)?'
            . $escapedTag
            . '\s*>/si';

        if (preg_match($pattern, $xml, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
