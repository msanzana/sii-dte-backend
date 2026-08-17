<?php
namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedDteToSiiResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteSiiSendDomainService;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadService;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\EnvioDteEnvelopeBuilderService;
use App\Modules\Dte\Infrastructure\Xml\EnvioDteSignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final class SendSignedDteToSiiUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteSiiSendDomainService $sendDomainService,
        private readonly EnvioDteEnvelopeBuilderService $envioDteEnvelopeBuilderService,
        private readonly EnvioDteSignatureService $envioDteSignatureService,
        private readonly SiiSoapAuthenticationService $siiSoapAuthenticationService,
        private readonly SiiFacturaUploadService $siiFacturaUploadService,
        private readonly DtePrivateStorageService $storageService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
    ){}

    public function execute(SendSignedDteToSiiInputDto $input): SendSignedDteToSiiResultDto
    {
        return DB::transaction(function () use($input) {
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);
            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->sendDomainService->assertCanSend($document);

            $company = $this->companyRepository->findById($document->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute(
                $document->companyId()
            );

            $absoluteSignedPath = storage_path($document->signedXmlPath());

            if(!File::exists($absoluteSignedPath))
            {
                throw new RuntimeException(
                    "No existe el XML firmado del documento en {$document->signedXmlPath()}."
                );
            }

            $signedDteXml = File::get($absoluteSignedPath);

            if($signedDteXml === false || trim($signedDteXml) === '')
            {
                throw new RuntimeException(
                    'No fue posible leer el XML firmado del DTE antes de construir el EnvioDTE.'
                );
            }

            $envelopeBuild = $this->envioDteEnvelopeBuilderService->build(
                document: $document,
                company: $company,
                signedDteXml: $signedDteXml
            );

            $signedEnvelopeXml = $this->envioDteSignatureService->SignSetDte(
                envioXml: $envelopeBuild['envelope_xml'],
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64,
                exponentBase64: $certificateContext->exponentBase64,
            );

            $envelopeFilename = sprintf(
                'envio_company_%d_td_%d_f_%d_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(random_bytes(4))
            );

            $requestBodyPath = $this->storageService->storeString(
                contents: $signedEnvelopeXml,
                targetDirectory: 'dispatch',
                targetFileName: $envelopeFilename
            );

            $token = $this->siiSoapAuthenticationService->authenticate(
                    environment:
                        $document->siiEnvironment()
                        ?? config('dte.default_environment'),

                    privateKeyPem:
                        $certificateContext->privateKeyPem,

                    certificateBase64:
                        $certificateContext->certificateBase64,

                    modulusBase64:
                        $certificateContext->modulusBase64,

                    exponentBase64:
                        $certificateContext->exponentBase64
            );

            [$companyRutBody, $companyRutDv] = $this->splitRut($company->rut());

            $senderRutBody=trim((string) config('dte.sii.sender.rut_body'));
            $senderRutDv = trim((string) config('dte.sii.sender.rut_dv'));

            $uploadResult = $this->siiFacturaUploadService->upload(
                environment: $document->siiEnvironment() ?? config('dte.default_environment'),
                token: $token,
                senderRutBody: $senderRutBody,
                senderRutDv: $senderRutDv,
                companyRutBody: $companyRutBody,
                companyRutDv: $companyRutDv,
                filename: $envelopeFilename,
                xmlBody: $signedEnvelopeXml,
                );

            $environment = $document->siiEnvironment() ?? config('dte.default_environment');
            $dispatch = new SiiDispatch(
                id:null,
                batchUuid: (string) Str::uuid(),
                companyId: $document->companyId(),
                dteDocumentId: $document->id(),
                environment: $document->siiEnvironment() ?? config('dte.default_environment'),
                transportType: 'soap_upload_factura',
                status: $uploadResult['status_code'] === '0' ? 'send' : 'upload_rejected',
                trackId: $uploadResult['track_id'],
                requestIdentifier: $envelopeBuild['set_dte_id'],
                requestPath: config("dte.sii.{$environment}.upload.url"),
                requestHeaders: [
                    'Cookie' => 'TOKEN=<redacted>',
                    'Content-Type' => 'multipart/form-data'
                ],
                requestBodyPath: $requestBodyPath,
                responseHttpStatus: $uploadResult['http_status'],
                responseBody: $uploadResult['raw_body'],
                uploadStatusCode: $uploadResult['status_code'],
                uploadStatusMessage: $uploadResult['status_message'],
                retryCount: 0,
                nextRetryAt: null,
                errorMessage: $uploadResult['status_code'] !== '0' ? $uploadResult['status_message'] : null,
                sentAt: now()->format('Y-m-d H:i:s'),
                lastPolledAt:null,
                processedAt: null,
            );

            $savedDispatch = $this->dispatchRepository->create($dispatch);

            if($uploadResult['status_code'] === '0')
            {
                $document = $document->withSentStatus();
                $this->documentRepository->update($document);
            }

            $this->logRepository->info(
                channel: 'sii_dispatch',
                message:'Envío automático al SII ejecutado.',
                context:[
                    'dispatch_id' => $savedDispatch->id(),
                    'document_id' => $document->id(),
                    'track_id' => $savedDispatch->trackId(),
                    'upload_status_code' => $savedDispatch->uploadStatusCode(),
                    'upload_status_message' => $savedDispatch->uploadStatusMessage(),
                ],
                companyId: $document->companyId(),
                documentId:$document->id(),
                code: 'SII_DISPATCH_CREATED'
            );

            return new SendSignedDteToSiiResultDto(
                dispatchId: $savedDispatch->id(),
                documentId: (int) $document->id(),
                batchUuid: $savedDispatch->batchUuid(),
                status: $savedDispatch->status(),
                trackId: $savedDispatch->trackId(),
                uploadStatusCode: $savedDispatch->uploadStatusCode(),
                uploadStatusMessage: $savedDispatch->uploadStatusMessage(),
                requestBodyPath: (string) $savedDispatch->requestBodyPath(),

            );
        });
    }

    private function splitRut(string $rut): array
    {
        $parts = explode('-',$rut);
        if (count($parts) !== 2)
        {
            throw new RuntimeException(
                "El RUT '{$rut}' no tiene el formato cuerpo-dv."
            );
        }

        return [trim($parts[0]),trim($parts[1])];
    }
}
