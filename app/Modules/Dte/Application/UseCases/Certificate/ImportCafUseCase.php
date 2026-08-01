<?php
namespace App\Modules\Dte\Application\UseCases\Certificate;

use App\Modules\Dte\Application\DTOs\ImportCafInputDto;
use App\Modules\Dte\Application\DTOs\ImportCafResultDto;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;
use App\Modules\Dte\Domain\Entities\SiiCaf;
use App\Modules\Dte\Domain\Exceptions\CafRangeOverlapException;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use App\Modules\Dte\Infrastructure\Xml\CafXmlParserService;
use Illuminate\Support\Facades\DB;

final class ImportCafUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DtePrivateStorageService $storageService,
        private readonly CafXmlParserService $cafXmlParserService,
        private readonly SecretEncryptionService $secretEncryptionService,
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        )
    {}
    public function execute(ImportCafInputDto $input): ImportCafResultDto
    {
        if(!$this->companyRepository->existsActiveById($input->companyId)) {
            throw CompanyNotFoundException::withId($input->companyId);
        }

        $xmlContents = file_get_contents($input->tempFilePath);

        if($xmlContents === false)
        {
            throw new \RuntimeException('No fue posible leer el archivo temporal del CAF.');
        }
        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
        );

        $parsed = $this->cafXmlParserService->parse($xmlContents);

        if(
            $this->cafRepository->existsOverlappingRange(
                companyId: $input->companyId,
                dteType: $parsed['dte_type'],
                folioStart: $parsed['folio_start'],
                folioEnd: $parsed['folio_end']
            )
        )
        {
            throw CafRangeOverlapException::forRange(
                dteType: $parsed['dte_type'],
                start: $parsed['folio_start'],
                end: $parsed['folio_end']
            );
        }

        return DB::transaction(function() use ($input, $xmlContents, $parsed)
        {
            $filename = sprintf(
                'Company_%d_td_%d_%d_%d_%s.xml',
                $input->companyId,
                $parsed['dte_type'],
                $parsed['folio_start'],
                $parsed['folio_end'],
                bin2hex(random_bytes(4))
            );

            $relativePath = $this->storageService->storeString(
                contents: $xmlContents,
                targetDirectory: 'caf',
                targetFileName: $filename
            );

            $encryptedPrivateMaterial = $this->secretEncryptionService->encrypt(
                $parsed['private_key_material']
            );

            $totalAuthorizedFolios = max(
                0,
                (int) $parsed['folio_end']
                    - (int) $parsed['folio_start']
                    + 1
            );

            $caf = new SiiCaf(
                id:null,
                companyId: $input->companyId,
                dteType: $parsed['dte_type'],
                folioStart: $parsed['folio_start'],
                folioEnd: $parsed['folio_end'],
                lastAssignedFolio:null,
                cafXmlPath: $relativePath,
                privateKeyPemEncrypted: $encryptedPrivateMaterial,
                publicKeyPem: $parsed['public_key_material'] ?? null,
                authorizedAt: $parsed['authorized_at'] ?? null,
                isActive:true,

                externalSystemId: $input->externalSystemId,

                requestedFoliosCount: $totalAuthorizedFolios,
                availableFoliosCount: $totalAuthorizedFolios,
                reservedFoliosCount: 0,
                usedFoliosCount: 0,
            );

            $saved = $this->cafRepository->create($caf);

            $this->logRepository->info(
                channel: 'caf',
                message: 'CAF importado correctamente',
                context: [
                    'caf_id' => $saved->id(),
                    'company_id' => $saved->companyId(),
                    'dte_type' => $saved->dteType(),
                    'folio_start' => $saved->folioStart(),
                    'folio_end' => $saved->folioEnd(),
                ],
                companyId: $saved->companyId(),
                code: 'CAF_IMPORTED'
            );

            return new ImportCafResultDto(
                id: $saved->id(),
                companyId: $saved->companyId(),
                externalSystemId: (int) $saved->externalSystemId(),
                dteType: $saved->dteType(),
                folioStart: $saved->folioStart(),
                folioEnd: $saved->folioEnd(),
                authorizedAt: $saved->authorizedAt(),
                cafXmlPath: $saved->cafXmlPath(),
                isActive: $saved->isActive(),
                requestedFoliosCount: $saved->requestedFoliosCount(),
                availableFoliosCount: $saved->availableFoliosCount(),
            );
        });
    }
}
