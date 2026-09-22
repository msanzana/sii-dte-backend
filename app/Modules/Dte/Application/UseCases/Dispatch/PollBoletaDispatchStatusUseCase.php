<?php
namespace App\Modules\Dte\Application\UseCases\Dispatch;

use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusInputDto;
use App\Modules\Dte\Application\DTOs\PollBoletaDispatchStatusResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DispatchNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteBoletaDispatchStatusDomainService;
use RuntimeException;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiSendStatusService;
use Illuminate\Support\Facades\DB;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;

final class PollBoletaDispatchStatusUseCase
{
    public function __construct(
        private readonly SiiDispatchRepositoryInterface $dispatchRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteBoletaDispatchStatusDomainService $boletaDispatchStatusDomainService,
        private readonly SiiBoletaApiSendStatusService $siiBoletaApiSendStatusService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
        // private readonly SiiCertificateRepositoryInterface $certificateRepository,
        // private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
        private readonly SiiBoletaTokenProviderService $siiBoletaTokenProviderService,
    )
    {}

    public function execute(
        PollBoletaDispatchStatusInputDto $input
    ): PollBoletaDispatchStatusResultDto
    {
        /*
        |--------------------------------------------------------------------------
        | FASE 1 | Preparar la consulta
        |--------------------------------------------------------------------------
        |
        | En esta fase solamente obtenemos y validamos los datos necesarios.
        | Todavía no mantenemos una transacción abierta mientras nos
        | comunicamos con el SII.
        |
        */

        $dispatch = $this->dispatchRepository->findById(
            $input->dispatchId
        );

        if (!$dispatch) {
            throw DispatchNotFoundException::withId(
                $input->dispatchId
            );
        }

        $this->boletaDispatchStatusDomainService->assertCanPoll(
            $dispatch
        );

        $company = $this->companyRepository->findById(
            $dispatch->companyId()
        );

        if (!$company || !$company->isActive()) {
            throw CompanyNotFoundException::withId(
                $dispatch->companyId()
            );
        }

        $certificateContext =
            $this->loadCertificateMaterialForEmisionService
                ->execute(
                    $dispatch->companyId()
                );

        [$companyRutBody, $companyRutDv] =
            $this->splitRut(
                $company->rut()
            );

        /*
        |--------------------------------------------------------------------------
        | FASE 2 | Comunicación externa con el SII
        |--------------------------------------------------------------------------
        |
        | TOKEN y consulta HTTP se ejecutan fuera de una transacción
        | de base de datos.
        |
        */

        $tokenContext =
            $this->siiBoletaTokenProviderService->get(
                environment: $dispatch->environment(),
                companyId: $certificateContext->companyId,
                certificateId: $certificateContext->certificateId,
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64,
                exponentBase64: $certificateContext->exponentBase64,
            );

        $token = $tokenContext['token'];

        $result =
            $this->siiBoletaApiSendStatusService->query(
                environment: $dispatch->environment(),
                token: $token,
                rutBody: $companyRutBody,
                rutDv: $companyRutDv,
                trackId: (string) $dispatch->trackId()
            );

        /*
        |--------------------------------------------------------------------------
        | FASE 3 | Persistir resultado
        |--------------------------------------------------------------------------
        |
        | La comunicación externa ya terminó. Ahora abrimos una transacción
        | corta solamente para persistir el resultado obtenido.
        |
        */

        return DB::transaction(
            function () use (
                $dispatch,
                $result
            ): PollBoletaDispatchStatusResultDto {
                $lockedDispatch =
                    $this->dispatchRepository->findByIdForUpdate(
                        (int) $dispatch->id()
                    );

                if (!$lockedDispatch) {
                    throw DispatchNotFoundException::withId(
                        (int) $dispatch->id()
                    );
                }
                $this->boletaDispatchStatusDomainService->assertCanPoll(
                    $lockedDispatch
                );
                $internalStatus =
                    $this->mapDispatchStatus(
                        $result['status_code']
                    );

                $processedAt = in_array(
                    $internalStatus,
                    [
                        'processed',
                        'rejected',
                    ],
                    true
                )
                    ? now()->format('Y-m-d H:i:s')
                    : null;

                $updateDispatch =
                    $lockedDispatch->withPollingResult(
                        status: $internalStatus,
                        responseBody: $result['raw_body'],
                        uploadStatusCode: $result['status_code'],
                        uploadStatusMessage: $result['status_message'],
                        errorMessage:
                            $internalStatus === 'rejected'
                                ? $result['status_message']
                                : null,
                        processedAt: $processedAt
                    );

                $savedDispatch =
                    $this->dispatchRepository->update(
                        $updateDispatch
                    );

                $this->logRepository->info(
                    channel: 'sii_boleta_dispatch',
                    message: 'Consulta REST de estado de envío de boleta ejecutada.',
                    context: [
                        'dispatch_id' =>
                            $savedDispatch->id(),

                        'track_id' =>
                            $savedDispatch->trackId(),

                        'status' =>
                            $savedDispatch->status(),

                        'send_status_code' =>
                            $savedDispatch->uploadStatusCode(),

                        'send_status_message' =>
                            $savedDispatch->uploadStatusMessage(),
                    ],
                    companyId:
                        $savedDispatch->companyId(),

                    documentId:
                        $savedDispatch->dteDocumentId(),

                    code:
                        'SII_BOLETA_DISPATCH_POLLED'
                );

                return new PollBoletaDispatchStatusResultDto(
                    dispatchId:
                        (int) $savedDispatch->id(),

                    status:
                        $savedDispatch->status(),

                    trackId:
                        $savedDispatch->trackId(),

                    sendStatusCode:
                        $savedDispatch->uploadStatusCode(),

                    sendStatusMessage:
                        $savedDispatch->uploadStatusMessage(),

                    rawBody:
                        (string) $savedDispatch->responseBody(),
                );
            }
        );
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

        if(in_array($code,['RCT','RPT','RFR','RPR','RSC'], true))
        {
            return 'rejected';
        }
        return 'polling';
    }
    private function splitRut(string $rut): array
    {
        $parts = explode(
            '-',
            $rut
        );

        if (count($parts) !== 2) {
            throw new RuntimeException(
                "El RUT '{$rut}' no tiene el formato cuerpo-dv."
            );
        }

        return [
            trim($parts[0]),
            trim($parts[1]),
        ];
    }
}
