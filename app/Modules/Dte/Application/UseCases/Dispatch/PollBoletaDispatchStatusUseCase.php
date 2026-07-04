<?php
namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusInputDto;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DispatchNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
//use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteBoletaDispatchStatusDomainService;
//use App\Modules\Dte\Infrastructure\Crypto\CertificateMaterialExtractorService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiSendStatusService;
//use App\Modules\Dte\Presentation\Http\Resources\CertificateNotFoundException;
use Illuminate\Support\Facades\DB;

final class PollBoletaDispatchStatusUseCase
{
    public function __construct(
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteBoletaDispatchStatusDomainService $boletaDispatchStatusDomainService,
        private readonly SiiBoletaApiAuthenticationService $siiBoletaApiAuthenticationService,
        private readonly SiiBoletaApiSendStatusService $siiBoletaApiSendStatusService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
        // private readonly SiiCertificateRepositoryInterface $certificateRepository,
        // private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
    )
    {}

    public function execute(
        PollBoletaDispatchStatusInputDto $input
    ): PollBoletaDispatchStatusResultDto
    {
        return DB::transaction(function () use ($input){
            $dispatch = $this->dispatchRepository->findById($input->dispatchId);

            if(!$dispatch)
            {
                throw DispatchNotFoundException::withId($input->dispatchId);
            }

            $this->boletaDispatchStatusDomainService->assertCanPoll($dispatch);

            $company = $this->companyRepository->findById($dispatch->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($dispatch->companyId());
            }

            // $certificate = $this->certificateRepository->findDefaultByCompanyId(
            //     $dispatch->companyId()
            // );

            // if(!$certificate)
            // {
            //     throw CertificateNotFoundException::defaultFromCompany(
            //         $dispatch->companyId()
            //     );
            // }

            // $certificateMaterial = $this->certificateMaterialExtractorService->extract(
            //     $certificate
            // );

            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute( $dispatch->companyId() );
            $token = $this->siiBoletaApiAuthenticationService->authenticate(
                environment: $dispatch->environment(),
                // privateKeyPem: $certificateMaterial['private_key_pem'],
                // certificateBase64: $certificateMaterial['certificate_base64'],
                // modulusBase64: $certificateMaterial['modulus_base64']
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64
            );

            $result = $this->siiBoletaApiSendStatusService->query(
                environment: $dispatch->environment(),
                token: $token,
                trackId: (string) $dispatch->trackId()
            );

            $internalStatus = $this->mapDispatchStatus($result['status_code']);

            $processedAt = in_array($internalStatus,['processed','rejected', true])
                            ? now()->format('Y-m-d H:i:s')
                            : null;

            $updateDispatch = $dispatch->withPollingResult(
                status: $internalStatus,
                responseBody: $result['raw_body'],
                uploadStatusCode: $result['status_code'],
                uploadStatusMessage: $result['status_message'],
                errorMessage:$internalStatus === 'regected' ? $result['status_message'] : null,
                processedAt: $processedAt
            );

            $savedDispatch = $this->dispatchRepository->update($updateDispatch);

            $this->logRepository->info(
                channel: 'sii_boleta_dispatch',
                message: 'ConsultaREST de estado de envío de boleta ejecutada.',
                context: [
                    'dispatch_id' => $savedDispatch->id(),
                    'track_id' => $savedDispatch->trackId(),
                    'status' => $savedDispatch->status(),
                    'send_status_code' => $savedDispatch->uploadStatusCode(),
                    'send_status_message' => $savedDispatch->uploadStatusMessage(),
                ],
                companyId: $savedDispatch->companyId(),
                documentId: $savedDispatch->dteDocumentId(),
                code: 'SII_BOLETA_DISPATCH_POLLED'
            );

            return new PollBoletaDispatchStatusResultDto(
                dispatchId: (int) $savedDispatch->id(),
                status: $savedDispatch->status(),
                trackId: $savedDispatch->trackId(),
                sendStatusCode: $savedDispatch->uploadStatusCode(),
                sendStatusMessage: $savedDispatch->uploadStatusMessage(),
                rawBody: (string) $savedDispatch->responseBody(),
            );
        });
    }

    private function mapDispatchStatus(?string $statusCode): string
    {
        $code = strtoupper(trim((string) $statusCode));

        if(in_array($code,['REC','SOK','FOK','PRD','CRT'], true))
        {
            return 'polling';
        }

        if(in_array($code,['EPR'], true))
        {
            return 'processed';
        }

        if(in_array($code,['RCT','RPT','RFR','RPR'], true))
        {
            return 'rejected';
        }
        return 'polling';
    }
}
