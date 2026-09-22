<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\EmissionCertificateMaterialContextDto;
use App\Modules\Dte\Application\DTOs\QuerySiiDocumentStatusInputDto;
use App\Modules\Dte\Application\DTOs\QuerySiiDocumentStatusResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteSiiDocumentStatusDomainService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiDocumentStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaDocumentStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiTokenProviderService;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
//use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use App\Modules\Dte\Application\Services\RecalculateCafCountersService;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaTokenProviderService;


final class QuerySiiDocumentStatusUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteSiiDocumentStatusDomainService $documentStatusDomainService,
       // private readonly SiiSoapAuthenticationService $siiSoapAuthenticationService,
        private readonly SiiTokenProviderService $siiTokenProviderService,
        private readonly SiiFacturaDocumentStatusService $siiFacturaDocumentStatusService,
        private readonly SiiBoletaApiDocumentStatusService $siiBoletaApiDocumentStatusService,
        private readonly SiiBoletaTokenProviderService $siiBoletaTokenProviderService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
        private readonly FolioStatusRepositoryInterface $folioStatusRepository,
        private readonly FolioDetailEventRepositoryInterface $folioDetailEventRepository,
        private readonly RecalculateCafCountersService $recalculateCafCountersService,
        // private readonly SiiCertificateRepositoryInterface $certificateRepository,
        // private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
    )
    {}

    public function execute(
        QuerySiiDocumentStatusInputDto $input
    ): QuerySiiDocumentStatusResultDto
    {
        return DB::transaction(function () use($input)
        {
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);
            if (!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->documentStatusDomainService->assertCanQueryStatus($document);

            $company = $this->companyRepository->findById($document->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute( $document->companyId());

            if($document->dteType()->isFacturaFamily())
            {
                return $this->queryFacturaFamily(
                    document: $document,
                    company: $company,
                    certificateContext: $certificateContext
                );
            }

            return $this->queryBoletaFamily(
                document: $document,
                company: $company,
                certificateContext: $certificateContext
            );
        });
    }

    private function queryFacturaFamily(
        $document,
        $company,
        EmissionCertificateMaterialContextDto $certificateContext
    ): QuerySiiDocumentStatusResultDto
    {
        $environment = $document->siiEnvironment() ?? config('dte.default_environment');

        $tokenContext = $this->siiTokenProviderService->get(
            environment: $environment,
            companyId: $document->companyId(),
            certificateId: $certificateContext->certificateId,
            privateKeyPem: $certificateContext->privateKeyPem,
            certificateBase64: $certificateContext->certificateBase64,
            modulusBase64: $certificateContext->modulusBase64,
            exponentBase64: $certificateContext->exponentBase64,
        );

        $token = $tokenContext['token'];

        [$consultantRutBody, $consultantRutDv] = $this->splitConfiguredSenderRut();
        [$companyRutBody, $companyRutDv] = $this->splitRut($company->rut());
        [$receiverRutBody, $receiverRutDv] = $this->splitRut($document->receiver()->document());

        $result = $this->siiFacturaDocumentStatusService->query(
            environment: $environment,
            consultantRutBody: $consultantRutBody,
            consultantRutDv:$consultantRutDv,
            companyRutBody: $companyRutBody,
            companyRutDv: $companyRutDv,
            receiverRutBody: $receiverRutBody,
            receiverRutDv: $receiverRutDv,
            dteType: (string) $document->dteType()->value,
            folio: (string) $document->folio(),
            issueDate: (new DateTimeImmutable($document->issueDate()))->format('dmY'),
            amount: (string) round($document->totalAmount(),0),
            token: $token
        );

        $updateDocument = $this->mapFacturaResultToDocument(
            document: $document,
            siiCode: $result['estado'],
            siiMessage: $result['glosa_err'] ?? $result['glosa']
        );

        $savedDocument = $this->documentRepository->update($updateDocument);
        $this->syncAcceptedFolioWithSii(
            document: $savedDocument
        );
        $this->syncCancelledFolioWithSii(
            document: $savedDocument,
            siiCode: $result['estado'],
            siiMessage: $result['glosa_err'] ?? $result['glosa'],
            attentionNumber: $result['num_atencion'] ?? null
        );
        $this->logRepository->info(
            channel: 'sii_document_status',
            message: 'Consulta QueryEstDte ejecutada.',
            context: [
                'document_id' => $savedDocument->id(),
                'sii_status_code' => $result['estado'],
                'sii_status_message' => $result['glosa_err'] ?? $result['glosa'],
                'num_atencion' => $result['num_atencion'],
            ],
            companyId: $savedDocument->companyId(),
            documentId: $savedDocument->id(),
            code: 'SII_QUERY_EST_DTE'
        );

        return new QuerySiiDocumentStatusResultDto(
            documentId: $savedDocument->id(),
            externalId: $savedDocument->externalId(),
            companyId: $savedDocument->companyId(),
            dteType: $savedDocument->dteType()->value,
            folio: (int) $savedDocument->folio(),
            queriedVia: 'query_est_dte_soap',
            siiStatusCode: $result['estado'],
            siiStatusMessage: $result['glosa_err'] ?? $result['glosa'],
            attentionNumber: $result['num_atencion'],
            internalStatus: $savedDocument->status(),
            rawBody: $result['raw_body'],
        );
    }

    private function QueryBoletaFamily(
        $document,
        $company,
        EmissionCertificateMaterialContextDto $certificateContext
    ): QuerySiiDocumentStatusResultDto
    {
        $environment = $document->siiEnvironment() ?? config('dte.default_environment');

        $tokenContext = $this->siiBoletaTokenProviderService->get(
            environment: $environment,
            companyId: $certificateContext->companyId,
            certificateId: $certificateContext->certificateId,
            privateKeyPem: $certificateContext->privateKeyPem,
            certificateBase64: $certificateContext->certificateBase64,
            modulusBase64: $certificateContext->modulusBase64,
            exponentBase64: $certificateContext->exponentBase64,
        );

        $token = $tokenContext['token'];

        [$companyRutBody, $companyRutDv] = $this->splitRut($company->rut());
        [$receiverRutBody, $receiverRutDv] = $this->splitRut($document->receiver()->document());

        $payload =[
            'rut_emisor' => $companyRutBody,
            'dv_emisor' => $companyRutDv,
            'tipo_dte' => $document->dteType()->value,
            'folio' => $document->folio(),
            'fecha_emision' => $document->issueDate(),
            'monto_total' => (int) round($document->totalAmount(),0),
            'rut_receptor' => $receiverRutBody,
            'dv_receptor' => $receiverRutDv,

        ];

        $result = $this->siiBoletaApiDocumentStatusService->query(
            environment: $environment,
            token: $token,
            payload: $payload
        );

        $updatedDocument = $this->mapBoletaResultToDocument(
            document: $document,
            siiCode: $result['status_code'],
            siiMessage: $result['status_message']
        );

        $savedDocument = $this->documentRepository->update($updatedDocument);

        $this->syncAcceptedFolioWithSii(
            document: $savedDocument
        );

        $this->syncCancelledFolioWithSii(
            document: $savedDocument,
            siiCode: $result['status_code'],
            siiMessage: $result['status_message']
        );

        $this->logRepository->info(
            channel: 'sii_document_status',
            message: 'Consulta REST de boleta ejecutada.',
            context: [
                'document_id' => $savedDocument->id(),
                'sii_status_code' => $result['status_code'],
                'sii_status_message' => $result['status_message'],

            ],
            companyId: $savedDocument->companyId(),
            documentId: $savedDocument->id(),
            code: 'SII_QUERY_BOLETA_STATUS'
        );

        return new QuerySiiDocumentStatusResultDto(
            documentId: $savedDocument->id(),
            externalId: $savedDocument->externalId(),
            companyId: $savedDocument->companyId(),
            dteType: $savedDocument->dteType()->value,
            folio: (int) $savedDocument->folio(),
            queriedVia: 'boleta_rest_api',
            siiStatusCode: $result['status_code'],
            siiStatusMessage: $result['status_message'],
            attentionNumber: null,
            internalStatus: $savedDocument->status(),
            rawBody: $result['raw_body'],
        );
    }
    private function mapFacturaResultToDocument(
        $document,
        ?string $siiCode,
        ?string $siiMessage
    ){
        $normalized =strtoupper(trim((string) $siiCode));

        if($normalized === 'DOK')
        {
            return $document->withAcceptedStatus();
        }

        if(in_array($normalized, ['DNK','TMD','TMC','MMD','MMC','AND','ANC'], true))
        {
            return $document->withAcceptedWithReparosStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }

        if($normalized === 'FAU')
        {
            return $document->withSentStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }

        if(in_array($normalized,['FNA','FAN','EMP'], true))
        {
            return $document->withRejectedStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }

        return $document;
    }

    private function mapBoletaResultToDocument(
        $document,
        ?string $siiCode,
        ?string $siiMessage,
    )
    {
        $normalizedCode = mb_strtolower(trim((string) $siiCode));
        $normalizedMessage = mb_strtolower(trim((string) $siiMessage));

        if(
            in_array(
                $normalizedCode,
                ['0','ok','aceptado','accepted','dok'],
                true
            )
            || str_contains($normalizedMessage,'accepted')
        )
        {
            return $document->withAcceptedStatus();
        }

        if(
            str_contains($normalizedMessage,'reparo')
            || in_array(
                $normalizedCode,
                [
                    'reparo',
                    'accepted_with_reparos',
                    'dnk',
                    'tmd',
                    'tmc',
                    'mmd',
                    'mmc',
                    'and',
                    'anc',
                ],
                true
            )
        )
        {
            return $document->withAcceptedWithReparosStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }
        if($normalizedCode === 'fau')
        {
            return $document->withSentStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }
        // if(
        //     str_contains($normalizedMessage, 'rechaz')
        //     || in_array($normalizedCode,['rechazado','rejected'],true)
        // )
        // {
        //     return $document->withRejectedStatus(
        //         code:$siiCode,
        //         message: $siiMessage
        //     );
        // }
        if(
            str_contains($normalizedMessage, 'rechaz')
            || in_array(
                $normalizedCode,
                [
                    'rechazado',
                    'rejected',
                    'fan',
                    'fna',
                    'emp',
                ],
                true
            )
        )
        {
            return $document->withRejectedStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }
        return $document;
    }

    private function splitRut(string $rut):array
    {
        $parts = explode('-', $rut);

        if(count($parts) !== 2)
        {
            throw new RuntimeException(
                "El RUT '{$rut}' no tiene formato cuerpo-dv"
            );

        }

        return [trim($parts[0]), trim($parts[1])];
    }

    private function splitConfiguredSenderRut(): array
    {
        $rutBody = trim((string) config('dte.sii.sender.rut_body'));
        $rutDv = trim((string) config('dte.sii.sender.rut_dv'));

        if($rutBody === '' || $rutDv === '')
        {
            throw new RuntimeException(
                'Falta configurar DTE_SII_SENDER_RUT_BODY o DTE_SII_SENDER_RUT_DV.'
            );
        }

        return [$rutBody, $rutDv];
    }
    private function syncAcceptedFolioWithSii($document): void
    {
        if (!in_array(
            $document->status(),
            ['accepted', 'accepted_with_reparos'],
            true
        )) {
            return;
        }

        $folioDetail = $this->folioDetailRepository
            ->findByDocumentIdForUpdate(
                $document->id()
            );

        if (!$folioDetail) {
            throw new RuntimeException(
                "No existe detalle de folio asociado al documento {$document->id()}."
            );
        }

        if ($folioDetail->folioStatusCode() === 'accepted_by_sii') {
            return;
        }

        $acceptedStatus = $this->folioStatusRepository
            ->findByCode('accepted_by_sii');

        if (!$acceptedStatus) {
            throw new RuntimeException(
                'No existe el estado de folio accepted_by_sii.'
            );
        }

        $usedAt = now()->format('Y-m-d H:i:s');

        $this->folioDetailRepository->updateReservationState(
            folioDetailId: $folioDetail->id(),
            folioStatusId: $acceptedStatus->id(),
            reserved: false,
            reservedAt: $folioDetail->reservedAt(),
            releasedAt: $folioDetail->releasedAt(),
            usedAt: $usedAt
        );

        $this->folioDetailEventRepository->create(
            folioDetailId: $folioDetail->id(),
            companyId: $folioDetail->companyId(),
            externalSystemId: $folioDetail->externalSystemId(),
            branchOfficeNumber: $folioDetail->branchOfficeNumber(),
            facilityNumber: $folioDetail->facilityNumber(),
            eventCode: 'accepted_by_sii',
            fromStatusCode: $folioDetail->folioStatusCode(),
            toStatusCode: 'accepted_by_sii',
            message: 'Folio aceptado por el SII.',
            userId: null,
            payloadJson: json_encode([
                'dte_document_id' => $document->id(),
                'folio' => $folioDetail->folioNumber(),
                'caf_id' => $folioDetail->cafId(),
            ], JSON_UNESCAPED_UNICODE)
        );

        $this->recalculateCafCountersService->execute(
            $folioDetail->cafId()
        );
    }
    private function syncCancelledFolioWithSii(
        $document,
        ?string $siiCode,
        ?string $siiMessage,
        ?string $attentionNumber = null
    ): void
    {
        $normalizedCode = strtoupper(
            trim((string) $siiCode)
        );

        if ($normalizedCode !== 'FAN') {
            return;
        }

        $folioDetail = $this->folioDetailRepository
            ->findByDocumentIdForUpdate(
                $document->id()
            );

        if (!$folioDetail) {
            throw new RuntimeException(
                "No existe detalle de folio asociado al documento {$document->id()}."
            );
        }

        if ($folioDetail->folioStatusCode() === 'cancelled') {
            return;
        }

        $cancelledStatus = $this->folioStatusRepository
            ->findByCode('cancelled');

        if (!$cancelledStatus) {
            throw new RuntimeException(
                'No existe el estado de folio cancelled.'
            );
        }

        $this->folioDetailRepository->updateReservationState(
            folioDetailId: $folioDetail->id(),
            folioStatusId: $cancelledStatus->id(),
            reserved: false,
            reservedAt: $folioDetail->reservedAt(),
            releasedAt: $folioDetail->releasedAt(),
            usedAt: null
        );

        $this->folioDetailEventRepository->create(
            folioDetailId: $folioDetail->id(),
            companyId: $folioDetail->companyId(),
            externalSystemId: $folioDetail->externalSystemId(),
            branchOfficeNumber: $folioDetail->branchOfficeNumber(),
            facilityNumber: $folioDetail->facilityNumber(),
            eventCode: 'cancelled_by_sii_reconciliation',
            fromStatusCode: $folioDetail->folioStatusCode(),
            toStatusCode: 'cancelled',
            message: 'Folio marcado como cancelado tras conciliación con SII: '
                . $normalizedCode
                . ' / '
                . trim((string) $siiMessage)
                . '.',
            userId: null,
            payloadJson: json_encode([
                'dte_document_id' => $document->id(),
                'folio' => $folioDetail->folioNumber(),
                'caf_id' => $folioDetail->cafId(),
                'sii_status_code' => $normalizedCode,
                'sii_status_message' => $siiMessage,
                'attention_number' => $attentionNumber,
            ], JSON_UNESCAPED_UNICODE)
        );

        $this->recalculateCafCountersService->execute(
            $folioDetail->cafId()
        );
    }

}
