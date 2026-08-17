<?php
namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusInputDto;
use App\Modules\Dte\Application\DTOs\PollSiiUploadStatusResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\DispatchNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaUploadStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PollSiiUploadStatusUseCase
{
    public function __construct(
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly SiiSoapAuthenticationService $siiSoapAuthenticationService,
        private readonly SiiFacturaUploadStatusService $siiFacturaUploadStatusService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,

        // private readonly SiiCertificateRepositoryInterface $certificateRepository,
        // private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
    )
    {}

    public function execute(PollSiiUploadStatusInputDto $input): PollSiiUploadStatusResultDto
    {
        return DB::transaction(function () use($input) {
            $dispatch = $this->dispatchRepository->findById($input->dispatchId);

            if(!$dispatch)
            {
                throw DispatchNotFoundException::withId($input->dispatchId);
            }

            if($dispatch->trackId() === null || $dispatch->trackId() === '')
            {
                throw new RuntimeException(
                    "El dispatch {$dispatch->id()} no tiene trackID para consultar estado."
                );
            }

            $company = $this->companyRepository->findById($dispatch->companyId());

            if(!$company || !$company->isActive())
            {
                throw new RuntimeException(
                    "La empresa {$dispatch->companyId()} asociada al dispatch no existe o está inactiva"
                );
            }


            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute($dispatch->companyId());

            $token = $this->siiSoapAuthenticationService->authenticate(
                environment: $dispatch->environment(),
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64,
                exponentBase64: $certificateContext->exponentBase64

            );

            [$companyRutBody, $companyRutDv] = $this->splitRut($company->rut());

            $queryResult= $this->siiFacturaUploadStatusService->query(
                environment: $dispatch->environment(),
                token: $token,
                companyRutBody: $companyRutBody,
                companyRutDv: $companyRutDv,
                trackId: (string) $dispatch->trackId()
            );

            $status = $this->mapDispatchStatus(
                $queryResult['estado'],
                $queryResult['error_code']
            );

            $processedAt = in_array(
                $status,
                [
                    'processed',
                    'rejected',
                    'failed'
                ],
                true)
                ? now()->format('Y-m-d H:i:s') : null;

            $updated = $dispatch->withPollingResult(
                status: $status,
                responseBody: $queryResult['raw_body'],
                uploadStatusCode: $queryResult['estado'] ?? $queryResult['error_code'],
                uploadStatusMessage: $queryResult['glosa'] ?? $queryResult['glosa_err'],
                errorMessage: $status === 'failed' ? $queryResult['glosa_err'] ?? $queryResult['glosa'] : null,
                processedAt: $processedAt
            );

            $saved = $this->dispatchRepository->update($updated);

            $this->logRepository->info(
                channel: 'sii_dispatch',
                message: 'consulta de estado de upload al SII ejecutada.',
                context: [
                    'dispatch_id' => $saved->id(),
                    'track_id' => $saved->trackId(),
                    'status' => $saved->status(),
                    'upload_status_code' => $saved->uploadStatusCode(),
                    'upload_status_message' => $saved->uploadStatusMessage(),
                ],
                companyId: $saved->companyId(),
                documentId: $saved->dteDocumentId(),
                code: 'SII_DISPATCH_POLLED'
            );

            return new PollSiiUploadStatusResultDto(
                dispatchId: $saved->id(),
                status: $saved->status(),
                trackId: $saved->trackId(),
                uploadStatusCode: $saved->uploadStatusCode(),
                uploadStatusMessage: $saved->uploadStatusMessage(),
                responseBody: $saved->responseBody(),
            );
        });
    }
    private function splitRut(string $rut): array
    {
        $parts = explode('-', $rut);

        if(count($parts) !==2)
        {
            throw new RuntimeException("El RUT {$rut} no tiene formato cuerpo-dv.");
        }
        return [trim($parts[0]), trim($parts[1])];
    }

    private function mapDispatchStatus(?string $estado, ?string $errorCode): string
    {
        if($estado !== null && in_array($estado,['EPR','RCT','EOK'],true))
        {
            return 'processed';
        }

        if($estado !== null && in_array($estado,['RCH','RSC'],true))
        {
            return 'rejected';
        }

        if($errorCode !== null && trim($errorCode) !== '')
        {
            return 'failed';

        }

        return 'polling';
    }

}
