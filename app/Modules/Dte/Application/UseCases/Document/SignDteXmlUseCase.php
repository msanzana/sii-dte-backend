<?php
namespace App\Modules\Dte\Application\UseCases\Document;


use App\Modules\Dte\Application\DTOs\SignDteXmlInputDto;use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Application\DTOs\SignDteXmlResultDto;
use App\Modules\Dte\Application\Services\LoadCertificateMaterialForEmisionService;
use App\Modules\Dte\Domain\Exceptions\DocumentNotFoundException;
use App\Modules\Dte\Domain\Exceptions\InvalidDocumentStateException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\Services\DteXmlSignDomainService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\DteXmlSignatureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class SignDteXmlUseCase
{
    public function __construct(
        private readonly DteDocumentRepositoryInterface $documentRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DteXmlSignatureService $dteXmlSignatureService,
        private readonly DtePrivateStorageService $storageService,
        private readonly DteXmlSignDomainService $xmlSignDomainService,
        private readonly LoadCertificateMaterialForEmisionService $loadCertificateMaterialForEmisionService,
    )
    {}

    public function execute(SignDteXmlInputDto $input): SignDteXmlResultDto
    {
        return DB::transaction(function () use ($input){
            $document = $this->documentRepository->findByIdForUpdate($input->documentId);

            if(!$document)
            {
                throw DocumentNotFoundException::withId($input->documentId);
            }

            $this->xmlSignDomainService->assertCanSignXml($document);

            $company = $this->companyRepository->findById($document->companyId());

            if(!$company || !$company->isActive())
            {
                throw CompanyNotFoundException::withId($document->companyId());
            }

            $certificateContext = $this->loadCertificateMaterialForEmisionService->execute(
                $document->companyId()
            );

            $absoluteUnsignedPath = storage_path($document->unsignedXmlPath());

            if(!File::exists($absoluteUnsignedPath))
            {
                throw InvalidDocumentStateException::because(
                    "El No existe el Xml unsigned del documento en {$document->unsignedXmlPath()}."
                );
            }

            $unsignedXml = File::get($absoluteUnsignedPath);

            if($unsignedXml === false || trim($unsignedXml) === '')
            {
                throw InvalidDocumentStateException::because(
                    'No fue posible leer el XML unsigned del documento antes del firmarlo.'
                );
            }

            $signatureResult = $this->dteXmlSignatureService->signDte(
                xmlWithTed: $unsignedXml,
                privateKeyPem: $certificateContext->privateKeyPem,
                certificateBase64: $certificateContext->certificateBase64,
                modulusBase64: $certificateContext->modulusBase64,
                exponentBase64: $certificateContext->exponentBase64,
            );

            $filename = sprintf(
                'dte_company_%d_td_%d_f_%d_signed_%s.xml',
                $document->companyId(),
                $document->dteType()->value,
                $document->folio(),
                bin2hex(random_bytes(4)),
            );

            $relativePath = $this->storageService->storeString(
                contents: $signatureResult['signed_xml'],
                targetDirectory: 'signed',
                targetFileName: $filename,
            );

            $updatedDocument = $document->withSignedXml($relativePath);

            $saved = $this->documentRepository->update($updatedDocument);

            $this->logRepository->info(
                channel: 'xml_sign',
                message: 'DTE firmado correctamente con XMLDSig',
                context: [
                    'document_id' => $saved->id(),
                    'external_id' => $saved->externalId(),
                    'company_id' => $saved->companyId(),
                    'dte_type' => $saved->dteType()->value,
                    'folio' => $saved->folio(),
                    //'certificate_id' => $certificate->id(),
                    'certificate_id' => $certificateContext->certificateId,
                    'document_xml_id' => $signatureResult['document_xml_id'],
                    'tmst_firma' => $signatureResult['tmst_firma'],
                    'signed_xml_path' => $relativePath,
                ],
                companyId: $saved->companyId(),
                documentId: $saved->id(),
                code:'DTE_XML_SIGNED'
            );

            return new SignDteXmlResultDto(
                documentId: $saved->id(),
                externalId: $saved->externalId(),
                companyId: $saved->companyId(),
                dteType: $saved->dteType()->value,
                folio: (int) $saved->folio(),
                certificateId: (int) $certificateContext->certificateId,
                documentXmlId: $signatureResult['document_xml_id'],
                tmstFirma: $signatureResult['tmst_firma'],
                status: $saved->status(),
                signedXmlPath: $relativePath,
            );
        });
    }
}
