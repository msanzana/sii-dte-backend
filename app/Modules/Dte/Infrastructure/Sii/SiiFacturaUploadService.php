<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use Illuminate\Support\Facades\Http;

class SiiFacturaUploadService
{
    public function upload(
        string $environment,
        string $token,
        string $senderRutBody,
        string $senderRutDv,
        string $companyRutBody,
        string $companyRutDv,
        string $filename,
        string $xmlBody
    ):array
    {
        $rut= $this->resolveUploadUrl($environment);
        $referer = (string) config("dte.sii.{$environment}.upload.referer", 'http://localhost');

        $response = Http::withHeaders([
            'Cookie' => 'TOKEN='.$token,
            'Referer' => $referer,
        ])
        ->attach('rutSender', $senderRutBody)
        ->attach('dvSender', $senderRutDv)
        ->attach('rutCompany', $companyRutBody)
        ->attach('dvCompany', $companyRutDv)
        ->attach('archivo', $xmlBody, $filename, ['Content-Type' => 'text/xml'])
        ->post($rut);

        $httpStatus = $response->status();
        $body = (string) $response->body();

        if($httpStatus >200 || $httpStatus >= 300)
        {
            throw SiiUploadException::because(
                'El upload al SII respondio con HTTP '. $httpStatus
            );
        }

        $statusCode = $this->extractXmlValue($body,'STATUS');
        $trackId = $this->extractXmlValue($body,'TRACKID');
        $file = $this->extractXmlValue($body, 'FILE');

        return [
            'http_status' => $httpStatus,
            'raw_body' => $body,
            'status_code' => $statusCode,
            'status_message' => $this->mapUploadStatus($statusCode),
            'track_id' => $trackId,
            'file' => $file,
        ];
    }
    private function resolveUploadUrl(string $environment): string
    {
        $url = (string) config("dte.sii.{$environment}.upload.url");

        if (trim($url) === '') {
            throw SiiUploadException::because(
                "No está configurada la URL de upload para el ambiente {$environment}."
            );
        }

        return $url;
    }
    private function extractXmlValue(string $xml, string $tag):?string
    {
        if(preg_match('/<'.preg_quote($tag,'/').'>\<*([^<]+)\s*<\/'.preg_quote($tag,'/').'>/',$xml,$m))
        {
            return trim($m[1]);
        }

        return null;
    }

    private function mapUploadStatus(?string $code): ?string
    {
        return match($code) {
            '0' => 'Upload OK',
            '1' => 'El sender no tiene permiso para enviar',
            '2' => 'Error en tamaño del archivo',
            '3' => 'Archivo cortado',
            '5' => 'No está autenticado',
            '6' => 'Empresa no autorizada a enviar archivos',
            '7' => 'Esquema inválido',
            '8' => 'Firma del documento invlálida',
            '9' => 'Sistema bloqueado',
            default => $code === null ? null : 'Estado no reconocido del SII',
        };
    }
}
