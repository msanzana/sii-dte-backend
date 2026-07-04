<?php
namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiInputDto;
use App\Modules\Dte\Application\DTOs\SendSignedBoletaToSiiResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\SiiDispatch;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
//use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteBoletaSendDomainService;
//use App\Modules\Dte\Infrastructure\Crypto\CertificateMaterialExtractorService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiUploadService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\EnvioBoletaEnvelopeBuilderService;
//use App\Modules\Dte\Presentation\Http\Resources\CertificateNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final class SendSignedBoletaToSiiUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteBoletaSendDomainService $boletaSendDomainService,
        private readonly SiiBoletaApiAuthenticationService $siiBoletaApiAuthenticationService,
        private readonly EnvioBoletaEnvelopeBuilderService $envioBoletaEnvelopeBuilderService,
        private readonly SiiBoletaApiUploadService $siiBoletaApiUploadService,
        private readonly DtePrivateStorageService $storageService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
        // private readonly SiiCertificateRepositoryInterface $certificateRepository,
        // private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
    ){}

    public function execute(
        SendSignedBoletaToSiiInputDto $input
    ): SendSignedBoletaToSiiResultDto
    {
        return DB::transaction(function () use ($input){
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);

            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->boletaSendDomainService->assertCanSend($document);

            $company = $this->companyRepository->findById($document->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            // $certificate = $this->certificateRepository->findDefaultByCompanyId($document->companyId());

            // if(!$certificate)
            // {
            //     throw CertificateNotFoundException::defaultFromCompany($document->companyId());
            // }

            // $certificateMaterial = $this->certificateMaterialExtractorService->extract(
            //     $certificate
            // );
            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute(
                $document->companyId()
            );
            $environment = $document->siiEnvironment() ?? config('dte.default_environment');

            $token = $this->siiBoletaApiAuthenticationService->authenticate(
                environment: $environment,
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64,
            );

            $absoluteSignedPath = storage_path($document->signedXmlPath());

            if(!File::exists($absoluteSignedPath))
            {
                throw new RuntimeException(
                    "No existe el XML firmado de boleta en {$document->signedXmlPath()}."
                );
            }

            $signedXml = File::get($absoluteSignedPath);

            if($signedXml === false || trim($signedXml) === '')
            {
                throw new RuntimeException(
                    'No fue posiblleer el XML firmado de Boleta antes del envío'
                );
            }

            $payloadBuild = $this->envioBoletaEnvelopeBuilderService->build(
                document: $document,
                company: $company,
                signedXml: $signedXml
            );

            $requestFilename = sprintf(
                'boleta_envio_company_%d_td_%d_f_%d_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(random_bytes(4))
            );

            $requestBodyPath = $this->storageService->storeString(
                contents:$payloadBuild['payload_xml'],
                targetDirectory:'dispatch_boleta',
                targetFileName: $requestFilename
            );

            $uploadResult = $this->siiBoletaApiUploadService->upload(
                environment: $environment,
                token: $token,
                filename: $requestFilename,
                xmlPayload: $payloadBuild['payload_xml']
            );

            $dispatchStatus = $this->mapDispatchStatusOnSend(
                $uploadResult['track_id'],
                $uploadResult['status_code']
            );

            $dispatch = new SiiDispatch(
                id: null,
                batchUuid: (string) Str::uuid(),
                companyId: $document->companyId(),
                dteDocumentId: $document->id(),
                environment: $environment,
                transportType: 'rest_upload_boleta',
                status: $dispatchStatus,
                trackId: $uploadResult['track_id'],
                requestIdentifier: $payloadBuild['request_identifier'],
                requestPath: config("dte.sii.boleta.{$environment}.send_url"),
                requestHeaders: [
                    'Content-Type' => config("dte.sii.boleta.{$environment}.send_content_type"),
                    config("dte.sii.boleta.{$environment}.token_header_name") => '<redacted>',
                ],
                requestBodyPath: $requestBodyPath,
                responseHttpStatus: $uploadResult['http_status'],
                responseBody: $uploadResult['raw_body'],
                uploadStatusCode: $uploadResult['status_code'],
                uploadStatusMessage: $uploadResult['status_message'],
                retryCount: 0,
                nextRetryAt: null,
                errorMessage: $dispatchStatus === 'upload_rejected'
                    ? $uploadResult['status_message']
                    : null,
                sentAt: now()->format('Y-m-d H:i:s'),
                lastPolledAt: null,
                processedAt: null,
            );

            $savedDispatch = $this->dispatchRepository->create($dispatch);

            if($dispatchStatus === 'sent'){
                $document = $document->withSentStatus();
                $this->documentRepository->update($document);
            }

            $this->logRepository->info(
                channel: 'sii_boleta_dispatch',
                message: 'Envio REST de boleta ejecutado.',
                context: [
                    'dispatch_id' => $savedDispatch->id(),
                    'document_id' => $document->id(),
                    'track_id' => $savedDispatch->trackId(),
                    'send_status_code' => $savedDispatch->uploadStatusCode(),
                    'send_status_message' => $savedDispatch->uploadStatusMessage(),
                ],
                companyId: $document->companyId(),
                documentId: $document->id(),
                code: 'SII_BOLETA_DISPATCH_CREATED'
            );

            return new SendSignedBoletaToSiiResultDto(
                dispatchId: $savedDispatch->id(),
                documentId: (int) $document->id(),
                batchUuid: $savedDispatch->batchUuid(),
                status: $savedDispatch->status(),
                trackId: $savedDispatch->trackId(),
                sendStatusCode: $savedDispatch->uploadStatusCode(),
                sendStatusMessage: $savedDispatch->uploadStatusMessage(),
                requestBodyPath: (string) $savedDispatch->requestBodyPath(),
            );
        });
    }

    private function mapDispatchStatusOnSend(?string $trackId, ?string $statusCode):string
    {
        if($trackId != null && trim($trackId) !== '')
        {
            return 'sent';
        }

        $normalized = mb_strtolower(trim((string) $statusCode));

        if(in_array($normalized, ['0','ok','accepted','aceptado','rec'], true))
        {
            return 'sent';
        }

        return 'upload_rejected';
    }

}
