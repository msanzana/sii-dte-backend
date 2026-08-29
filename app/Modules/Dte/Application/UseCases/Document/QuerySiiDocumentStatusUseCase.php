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
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiAuthenticationService;
use App\Modules\Dte\Infrastructure\Sii\SiiBoletaApiDocumentStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiFacturaDocumentStatusService;
use App\Modules\Dte\Infrastructure\Sii\SiiSoapAuthenticationService;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class QuerySiiDocumentStatusUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteSiiDocumentStatusDomainService $documentStatusDomainService,
        private readonly SiiSoapAuthenticationService $siiSoapAuthenticationService,
        private readonly SiiFacturaDocumentStatusService $siiFacturaDocumentStatusService,
        private readonly SiiBoletaApiAuthenticationService $siiBoletaApiAuthenticationService,
        private readonly SiiBoletaApiDocumentStatusService $siiBoletaApiDocumentStatusService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
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

        $token = $this->siiSoapAuthenticationService->authenticate(
            environment: $environment,
            privateKeyPem: $certificateContext->privateKeyPem,
            certificateBase64: $certificateContext->certificateBase64,
            modulusBase64: $certificateContext->modulusBase64,
            exponentBase64: $certificateContext->exponentBase64,
        );

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

        $token = $this->siiBoletaApiAuthenticationService->authenticate(
            environment: $environment,
            privateKeyPem: $certificateContext->privateKeyPem,
            certificateBase64: $certificateContext->certificateBase64,
            modulusBase64: $certificateContext->modulusBase64
        );

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
            siiStatusMessage: $result['status_code'],
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

        if(in_array($normalized, ['DNK','TMD','TMC','MMD'], true))
        {
            return $document->withAcceptedWithReparosStatus(
                code: $siiCode,
                message: $siiMessage
            );
        }

        if(in_array($normalized,['FAU','FNA','FAN','EMP'], true))
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
            in_array($normalizedCode,['0','ok','aceptado','accepted'], true)
            || str_contains($normalizedMessage,'accepted')
        )
        {
            return $document->withAcceptedStatus();
        }

        if(
            str_contains($normalizedMessage,'reparo')
            || in_array($normalizedCode,['reparo','accepted_with_reparos'],true)
        )
        {
            return $document->withAcceptedWithReparosStatus(
                code:$siiCode,
                message: $siiMessage
            );
        }

        if(
            str_contains($normalizedMessage, 'rechaz')
            || in_array($normalizedCode,['rechazado','rejected'],true)
        )
        {
            return $document->withRejectedStatus(
                code:$siiCode,
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

}
