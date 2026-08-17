<?php
namespace App\Modules\Dte\Application\UseCases\Document;

use App\Modules\Dte\Application\DTOs\BuildTedInputDto;
use App\Modules\Dte\Application\DTOs\BuildTedResultDto;
use App\Modules\Dte\Application\Services\DteTedBuildDomainService;
use App\Modules\Dte\Domain\Exceptions\CafNotFoundForFolioException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Domain\Services\TedDataAssemblerService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\CafTedMaterialExtractorService;
use App\Modules\Dte\Infrastructure\Xml\DteTedEmbedderService;
use App\Modules\Dte\Infrastructure\Xml\TedSignatureService;
use App\Modules\Dte\Infrastructure\Xml\TedXmlBuilderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class BuildTedUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteTedBuildDomainService $tedBuildDomainService,
        private readonly TedDataAssemblerService $tedDataAssemblerService,
        private readonly CafTedMaterialExtractorService $cafTedMaterialExtractorService,
        private readonly TedXmlBuilderService $tedXmlBuilderService,
        private readonly TedSignatureService $tedSignatureService,
        private readonly DteTedEmbedderService $dteTedEmbedderService,
        private readonly DtePrivateStorageService $storageService,
    )
    {}

    public function execute(BuildTedInputDto $input): BuildTedResultDto
    {
        return DB::transaction(function () use ($input): BuildTedResultDto {
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);

            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->tedBuildDomainService->assertCanBuildTed($document);

            $company = $this->companyRepository->findById($document->companyId());

            if (!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            $caf = $this->cafRepository->findActiveContainingFolio(
                companyId: $document->companyId(),
                dteType: $document->dteType()->value,
                folio: (int) $document->folio(),
            );

            if(!$caf)
            {
                throw CafNotFoundForFolioException::forDocument(
                    companyId: $document->companyId(),
                    dteType: $document->dteType()->value,
                    folio: (int) $document->folio(),
                );
            }

            $cafMaterial = $this->cafTedMaterialExtractorService->extract($caf);

            $tedData = $this->tedDataAssemblerService->assemble(
                document: $document,
                company: $company,
                cafXmlFragment: $cafMaterial['caf_xml_fragment'],
            );

            $ddXml = $this->tedXmlBuilderService->buildDdXml($tedData);

            $frmtBase64 = $this->tedSignatureService->singDdXml(
                ddXmlUtf8: $ddXml,
                privateKeyPem: $cafMaterial['private_key_pem'],
            );

            $tedXml = $this->tedXmlBuilderService->buildTedXml(
                data: $tedData,
                frmtBase64: $frmtBase64,
            );

            $absoluteUnsignedXmlPath = storage_path($document->unsignedXmlPath());

            if(!File::exists($absoluteUnsignedXmlPath))
            {
                throw new \RuntimeException(
                    "No existe el XML base del documento en {$document->unsignedXmlPath()}."
                );
            }

            $existingDteXml = File::get($absoluteUnsignedXmlPath);

            if($existingDteXml === false || trim($existingDteXml) === '')
            {
                throw new \RuntimeException(
                    'No fue posible leer el XML base del documento antes de incrustar el TED.'
                );
            }

            $xmlWithTed = $this->dteTedEmbedderService->embedTed(
                dteXmlIso88591: $existingDteXml,
                tedXmlUtf8: $tedXml,
            );

            $filename = sprintf(
                'dte_company_%d_td_%d_f_%d_ted_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(random_bytes(4))
            );

            $relativePath = $this->storageService->storeString(
                contents: $xmlWithTed,
                targetDirectory: 'xml',
                targetFileName: $filename,
            );

            $updatedDocument = $document->withTedBuilt(
                tedXml: $tedXml,
                unsignedXmlPath: $relativePath
            );

            $saved = $this->documentRepository->update($updatedDocument);

            $this->logRepository->info(
                channel: 'ted_build',
                message: 'TED construido e incrustado correctamente en el Xml del DTE..',
                context: [
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'dte_type' => $saved->companyId(),
                    'folio' => $saved->folio(),
                    'caf_id' => $caf->id(),
                    'unsigned_xml_path' => $relativePath,
                ],
                companyId: $saved->companyId(),
                documentId:$saved->id(),
                code: 'DTE_TED_BUILT'
            );

            return new BuildTedResultDto(
                documentId: $saved->id(),
                externalId: $saved->externalId(),
                companyId: $saved->companyId(),
                dteType: $saved->dteType()->value,
                folio: (int) $saved->folio(),
                cafId: (int) $caf->id(),
                status: $saved->status(),
                unsignedXmlPath: $relativePath,
                tedXml: $tedXml,
            );
        });
    }
}
