<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\BuildDteXmlInputDto;
use App\Modules\Dte\Application\DTOs\BuildDteXmlResultDto;
use App\Modules\Dte\Application\Services\DteXmlDataAssemblerService;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\LocationSummaryNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\LocationRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteXmlBuildDomainService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\DteXmlBuilderService;
use Illuminate\Support\Facades\DB;

class BuildDteXmlUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteXmlBuildDomainService $xmlBuildDomainService,
        private readonly DteXmlDataAssemblerService $xmlDataAssemblerService,
        private readonly DteXmlBuilderService $xmlBuilderService,
        private readonly DtePrivateStorageService $storageService,
    )
    {}

    public function execute(BuildDteXmlInputDto $input): BuildDteXmlResultDto
    {
        return DB::transaction( function () use ($input){
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);

            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->xmlBuildDomainService->assertCanBuildXml($document);

            $company = $this->companyRepository->findById($document->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            $emitterLocation = $this->locationRepository->findSummaryByCityId($company->cityId());

            $receiverLocation = null;

            if($document->receiver()->cityId() !== null)
            {
                $receiverLocation = $this->locationRepository->findSummaryByCityId(
                    $document->receiver()->cityId(),
                );

                if(!$receiverLocation)
                {
                    throw LocationSummaryNotFoundException::withCityId(
                        $document->receiver()->cityId()
                    );
                }
            }

            $xmlData = $this->xmlDataAssemblerService->assemble(
                document: $document,
                company: $company,
                emitterLocation: $emitterLocation,
                receiverLocation: $receiverLocation,
            );

            $xml = $this->xmlBuilderService->build($xmlData);

            $filename = sprintf(
                'dte_company_%d_td_%d_f_%d_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(random_bytes(4))
            );

            $relativePath = $this->storageService->storeString(
                contents: $xml,
                targetDirectory: 'xml',
                targetFileName: $filename,
            );

            $updatedDocument = $document->withUnsignedXmlBuilt($relativePath);

            $saved = $this->documentRepository->update($updatedDocument);

            $this->logRepository->info(
                channel: 'xml_build',
                message: 'XML base del DTE construido correctamente.',
                context: [
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'folio' => $saved->folio(),
                    'unsigned_xml_path' => $relativePath,
                    'document_xml_id' => $xmlData['document_xml_id'],
                ],
                companyId: $saved->companyId(),
                documentId:$saved->id(),
                code: 'DTE_XML_BUILT'
            );

            return new BuildDteXmlResultDto(
                documentId: $saved->id(),
                externalId: $saved->externalId(),
                companyId: $saved->companyId(),
                dteType: $saved->dteType()->value,
                folio: (int) $saved->folio(),
                documentXmlId: $xmlData['document_xml_id'],
                status: $saved->status(),
                siiEnvironment: $saved->siiEnvironment() ?? $company->siiEnvironment(),
                unsignedXmlPath: $relativePath,
            );
        });
    }
}
