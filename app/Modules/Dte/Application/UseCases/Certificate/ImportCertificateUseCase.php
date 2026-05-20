<?php
namespace App\Modules\Dte\Application\UseCases\Certificate;

use App\Modules\Dte\Application\DTOs\ImportCertificateInputDto;
use App\Modules\Dte\Application\DTOs\ImportCertificateResultDto;
use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\Exceptions\CompanyNotFoundException;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use App\Modules\Dte\Infrastructure\Crypto\PfxInspectorService;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use Illuminate\Support\Facades\DB;

final class ImportCertificateUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SiiCertificateRepositoryInterface $certificateRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
        private readonly DtePrivateStorageService $storageService,
        private readonly PfxInspectorService $pfxInspectorService,
        private readonly SecretEncryptionService $secretEncryptionService,
    )
    {}
    public function execute(ImportCertificateInputDto $input):ImportCertificateResultDto
    {
        if(!$this->companyRepository->existsActiveById($input->companyId)){
            throw CompanyNotFoundException::withId($input->companyId);
        }
        return DB::transaction(function () use ($input) {
            $filename = sprintf(
                'company_%d_%d_%d.pfx',
                $input->companyId,
                date('Ymd_His'),
                bin2hex(random_bytes(4))
            );

            $relativePath = $this->storageService->storeFileFromPath(
                sourcePath: $input->tempFilepath,
                targetDirectory: 'certificates',
                targetFilename: $filename
            );

            $pfxContents = file_get_contents($input->tempFilepath);

            if($pfxContents === false)
            {
                throw new \RuntimeException('No fue posible el archivo temporal del certificado.');
            }

            $metadata = $this->pfxInspectorService->inspect(
                pfxContents: $pfxContents,
                password: $input->pfxPassword
            );

            $encryptedPassword = $this->secretEncryptionService->encrypt($input->pfxPassword);

            $isDefault = !$this->certificateRepository->hasDefaultForCompany($input->companyId);

            $certificate = new SiiCertificate(
                id: null,
                companyId: $input->companyId,
                alias: $input->alias,
                pfxPath: $relativePath,
                pfxPasswordEncrypted: $encryptedPassword,
                serialNumber: $metadata['serial_number'] ?? null,
                subjectName: $metadata('subject_name') ?? null,
                issuerName: $metadata('issuer_name') ?? null,
                validFrom: $metadata('valid_from') ?? null,
                validTo: $metadata('valid_to') ?? null,
                isDefault: $isDefault,
                isActive:true,

            );

            $saved = $this->certificateRepository->create($certificate);

            $this->logRepository->info(
                channel: 'certificate',
                message: 'Certificado importado correcxtamente',
                context: [
                    'certificate_id' => $saved->id(),
                    'company_id' => $saved->companyId(),
                    'alias' => $saved->alias(),
                    'serial_number' => $saved->serialNumber(),
                ],
                companyId: $saved->companyId(),
                code: 'CERTIFICATE_IMPORTED'
            );

            return new ImportCertificateResultDto(
                id: $saved->id(),
                companyId: $saved->companyId(),
                alias: $saved->alias(),
                pfxPath: $saved->pfxPath(),
                serialNumber: $saved->serialNumber(),
                subjectName: $saved->subjectName(),
                issuerName: $saved->issuerName(),
                validFrom: $saved->validFrom(),
                validTo: $saved->validTo(),
                isDefault: $saved->isDefault(),
                isActive:$saved->isActive(),
            );
        });
    }
}
