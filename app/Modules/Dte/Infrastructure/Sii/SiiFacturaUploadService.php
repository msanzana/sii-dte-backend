<?php
namespace App\Modules\Dte\Infrastructure\Sii;

use App\Modules\Dte\Domain\Exceptions\SiiUploadException;
use App\Modules\Dte\Infrastructure\Sii\Exceptions\SiiUploadTransportException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
class SiiFacturaUploadService
{
    public function __construct(
        private readonly SiiRequestThrottleService $requestThrottleService,
    ) {
    }
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
        $url = $this->resolveUploadUrl($environment);
        $referer = (string) config("dte.sii.{$environment}.upload.referer", 'http://localhost');
        $responseBuffer = fopen('php://temp', 'w+');

        if ($responseBuffer === false) {
            throw SiiUploadException::because(
                'No fue posible crear el buffer temporal para la respuesta del SII.'
            );
        }
        
        $httpStatus = null;
        $body = '';
        $transportDiagnostics = [];
        $transportRecovered = false;

        try {
            /*
            | El SII recomienda evitar ráfagas de solicitudes. El throttle
            | se comparte con CrSeed/GetToken para espaciar las llamadas.
            */
            $this->requestThrottleService->wait($environment);

            $response = Http::withOptions([
                'sink' => $responseBuffer,
            ])
                ->connectTimeout(20)
                ->timeout(60)
                ->withHeaders([
                    'Cookie' => 'TOKEN='.$token,

                    'Referer' => $referer,

                    'User-Agent' =>
                        'Mozilla/4.0 (compatible; PROG 1.0; Laravel DTE Client)',

                    'Accept' => '*/*',

                    'Accept-Language' => 'es-cl',

                    'Cache-Control' => 'no-cache',
                ])
                ->attach(
                    'rutSender',
                    $senderRutBody
                )
                ->attach(
                    'dvSender',
                    $senderRutDv
                )
                ->attach(
                    'rutCompany',
                    $companyRutBody
                )
                ->attach(
                    'dvCompany',
                    $companyRutDv
                )
                ->attach(
                    'archivo',
                    $xmlBody,
                    $filename,
                    [
                        'Content-Type' => 'text/xml',
                    ]
                )
                ->post(
                    $url
                );

            $httpStatus =
                $response->status();

            $body =
                $this->readResource(
                    $responseBuffer
                );

            $transportDiagnostics =
                $this->filterTransportDiagnostics(
                    $response->handlerStats()
                );

        } catch (ConnectionException $e) {

            /*
            |--------------------------------------------------------------------------
            | Recuperar toda la información posible del transporte
            |--------------------------------------------------------------------------
            */

            $sinkBody =
                $this->readResource(
                    $responseBuffer
                );

            $previous =
                $e->getPrevious();

            $responseBody = '';

            $handlerContext = [];

            /*
            |--------------------------------------------------------------------------
            | Laravel envuelve la excepción original de Guzzle
            |--------------------------------------------------------------------------
            |
            | Desde Guzzle podemos intentar obtener:
            |
            | - código HTTP
            | - respuesta parcial
            | - datos de cURL
            |
            */

            if ($previous instanceof GuzzleRequestException) {

                $handlerContext =
                    $previous->getHandlerContext();

                if ($previous->hasResponse()) {

                    $previousResponse =
                        $previous->getResponse();

                    $httpStatus =
                        $previousResponse?->getStatusCode();

                    $responseBody =
                        $previousResponse === null
                            ? ''
                            : $this->readResponseBody(
                                $previousResponse
                            );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Recuperar HTTP desde el contexto cURL
            |--------------------------------------------------------------------------
            */

            if (
                $httpStatus === null
                &&
                isset($handlerContext['http_code'])
            ) {

                $candidateHttpStatus =
                    (int) $handlerContext['http_code'];

                $httpStatus =
                    $candidateHttpStatus > 0
                        ? $candidateHttpStatus
                        : null;
            }

            /*
            |--------------------------------------------------------------------------
            | Podemos haber recibido datos por dos caminos
            |--------------------------------------------------------------------------
            |
            | 1. El sink php://temp
            | 2. La Response interna de Guzzle
            |
            | Conservamos la respuesta más completa.
            |
            */

            $body =
                $this->chooseMostCompleteBody(
                    $sinkBody,
                    $responseBody
                );

            $transportDiagnostics =
                $this->filterTransportDiagnostics(
                    $handlerContext
                );

            /*
            |--------------------------------------------------------------------------
            | Intentar interpretar la respuesta parcial/recuperada
            |--------------------------------------------------------------------------
            */

            $statusCode =
                $this->extractXmlValue(
                    $body,
                    'STATUS'
                );

            $trackId =
                $this->extractXmlValue(
                    $body,
                    'TRACKID'
                );

            /*
            |--------------------------------------------------------------------------
            | ¿La respuesta del SII es concluyente?
            |--------------------------------------------------------------------------
            |
            | Si tenemos un RECEPCIONDTE completo, el cierre TLS posterior no debe
            | invalidar automáticamente la respuesta.
            |
            | STATUS = 0 exige TRACKID.
            |
            | Para STATUS != 0 el SII puede entregar una respuesta válida sin TRACKID.
            |
            */

            if (
                $this->isCompleteReceptionBody($body)
                &&
                $statusCode !== null
                &&
                (
                    $statusCode !== '0'
                    ||
                    (
                        $trackId !== null
                        &&
                        trim($trackId) !== ''
                    )
                )
            ) {

                /*
                | Hubo error de transporte, pero recuperamos
                | una respuesta SII completa y utilizable.
                */

                $httpStatus ??= 200;

                $transportRecovered = true;

            } else {

                /*
                | No tenemos evidencia suficiente para afirmar
                | que el SII recibió correctamente el envío.
                |
                | El UseCase lo tratará como delivery_unknown.
                */

                throw new SiiUploadTransportException(
                    message:
                        $e->getMessage(),

                    diagnostics:
                        $transportDiagnostics,

                    httpStatus:
                        $httpStatus,

                    recoveredBody:
                        $body !== ''
                            ? $body
                            : null,

                    previous:
                        $e,
                );
            }

        } finally {

            if (is_resource($responseBuffer)) {

                fclose(
                    $responseBuffer
                );
            }
        }
        if ($httpStatus === null) {
            throw SiiUploadException::because(
                'El upload al SII terminó sin código HTTP disponible.'
            );
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw SiiUploadException::because(
                'El upload al SII respondió con HTTP '
                .$httpStatus
                .'. Body: '
                .mb_substr($body, 0, 2000)
            );
        }

        $statusCode = $this->extractXmlValue($body,'STATUS');
        $trackId = $this->extractXmlValue($body,'TRACKID');
        $file = $this->extractXmlValue($body, 'FILE');
        if ($statusCode === null) {
            throw SiiUploadException::because(
                'La respuesta HTTP del upload al SII no contiene STATUS. Body: '
                .mb_substr($body, 0, 2000)
            );
        }
        if (
            $statusCode === '0'
            &&
            (
                $trackId === null
                ||
                trim($trackId) === ''
            )
        ) {
            throw SiiUploadException::because(
                'El SII respondió STATUS=0 pero no entregó TRACKID.'
            );
        }
        
        return [
            'http_status' =>
                $httpStatus,

            'raw_body' =>
                $body,

            'status_code' =>
                $statusCode,

            'status_message' =>
                $this->mapUploadStatus(
                    $statusCode
                ),

            'track_id' =>
                $trackId,

            'file' =>
                $file,

            'transport_diagnostics' =>
                $transportDiagnostics,

            'transport_recovered' =>
                $transportRecovered,
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

        if (
            preg_match(
                '/<'
                    .preg_quote($tag, '/')
                    .'>\s*([^<]+?)\s*<\/'
                    .preg_quote($tag, '/')
                    .'>/s',

                $xml,

                $matches
            )
        ) {

            return trim(
                $matches[1]
            );
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
            '8' => 'Firma del documento inválida',
            '9' => 'Sistema bloqueado',
            default => $code === null ? null : 'Estado no reconocido del SII',
        };
    }
    private function isCompleteReceptionBody(
        string $body
    ): bool {

        return str_contains(
            $body,
            '<RECEPCIONDTE'
        )
            &&
            str_contains(
                $body,
                '</RECEPCIONDTE>'
            );
    }

    private function readResource(
        mixed $resource
    ): string {

        if (! is_resource($resource)) {
            return '';
        }

        rewind(
            $resource
        );

        $contents =
            stream_get_contents(
                $resource
            );

        return $contents === false
            ? ''
            : $contents;
    }
    private function readResponseBody(
        ResponseInterface $response
    ): string {

        return $this->readPsrStream(
            $response->getBody()
        );
    }

    private function readPsrStream(
        StreamInterface $stream
    ): string {

        try {

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $contents =
                $stream->getContents();

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            return $contents;

        } catch (\Throwable) {

            return '';
        }
    }

    private function chooseMostCompleteBody(
        string $sinkBody,
        string $responseBody
    ): string {

        if (
            strlen($responseBody)
            >
            strlen($sinkBody)
        ) {
            return $responseBody;
        }

        return $sinkBody;
    }

    private function filterTransportDiagnostics(
        array $data
    ): array {

        $allowed = [
            'errno',
            'error',
            'http_code',
            'total_time',
            'namelookup_time',
            'connect_time',
            'appconnect_time',
            'pretransfer_time',
            'starttransfer_time',
            'redirect_count',
            'size_upload',
            'size_download',
            'speed_upload',
            'speed_download',
            'upload_content_length',
            'download_content_length',
            'primary_ip',
            'local_ip',
            'local_port',
            'http_version',
            'scheme',
        ];

        return array_intersect_key(
            $data,
            array_flip(
                $allowed
            )
        );
    }
}

