<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\PrepareDteDocumentForXmlInputDto;
use App\Modules\Dte\Application\DTOs\PrepareDteDocumentForXmlResultDto;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteDocumentPreparationDomainService;
use Illuminate\Support\Facades\DB;

final class PrepareDteDocumentForXmlUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteDocumentPreparationDomainService $preparationDomainService,

    )
    {}

    public function execute(
        PrepareDteDocumentForXmlInputDto $input
    ): PrepareDteDocumentForXmlResultDto{
        return DB::transaction(function () use ($input) {
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);

            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->preparationDomainService->assertCanPrepareForXml($document);

            $company = $this->companyRepository->findById($document->companyId());

            if (!$company || !$company->isActive()) {
                throw CompanyNotFoundException::withId($document->companyId());
            }
            $reservedFolio = $this->cafRepository->reserveNextAvailableFolio(
                companyId: $document->companyId(),
                dteType: $document->dteType()->value,
            );

            $preparedDocument = $document->withFolioAndStatus(
                folio: $reservedFolio->folio(),
                status: DteStatus::FOLIO_ASSIGNED->value,
                siiEnvironment: $company->siiEnvironment(),
            );

            $saved = $this->documentRepository->update($preparedDocument);

            $this->logRepository->info(
                channel: 'document:workflow',
                message: 'Documento preparado para construccion de XML con folio reservado.',
                context:[
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'company_id'=> $saved->companyId(),
                    'dte_type'=> $saved->dteType()->value,
                    'caf_id' => $reservedFolio->cafId(),
                    'folio' => $reservedFolio->folio(),
                ],
                companyId: $saved->companyId(),
                documentId: $saved->id(),
                code: 'DOCUMENT_PREPARED_FOR_XML'
            );

            return new PrepareDteDocumentForXmlResultDto(
                documentId: $saved->id(),
                externalId: $saved->externalId(),
                companyId: $saved->companyId(),
                dteType: $saved->dteType()->value,
                cafId: $reservedFolio->cafId(),
                folio: $reservedFolio->folio(),
                cafFolioStart: $reservedFolio->cafFolioStart(),
                cafFolioEnd: $reservedFolio->cafFolioEnd(),
                status: $saved->status(),
                siiEnvironment: $saved->siiEnvironment() ?? $company->siiEnvironment(),
            );
        });
    }
}
