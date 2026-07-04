<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\EmissionCertificateContextDto;
use App\Modules\Dte\Application\Services\ResolveCertificateForEmisionService;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Infrastructure\Crypto\SecretEncryptionService;
use App\Modules\Dte\Infrastructure\Storage\DtePrivateStorageService;
use RuntimeException;

final class LoadCertificateForEmisionService
{
    public function __construct(
        private readonly ResolveCertificateForEmisionService $resolveCertificateForEmisionService,
        private readonly DtePrivateStorageService $storageService,
        private readonly SecretEncryptionService $secretEncryptionService,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ){}

    public function execute(int $companyId): EmissionCertificateContextDto
    {
        $certificate = $this->resolveCertificateForEmisionService->execute($companyId);

        $pfxContents = $this->storageService->getContents($certificate->pfxPath());

        if($pfxContents === null || $pfxContents === '')
        {
            $this->logRepository->error(
                channel: 'certificate',
                message: 'No fue posible leer el archivo PFX del certificado de emisión.',
                context: [
                    'copmpany_id' => $companyId,
                    'certificate_id' => $certificate->id(),
                    'pfx_path' => $certificate->pfxPath(),
                ],
                companyId: $companyId,
                code: 'CERTIFICATE_PFX_NOT_READABLE',
            );
            throw new RuntimeException(
                'No fue posible leer el archivo del certificado digital de la empresa.'
            );
        }

        $decryptedPassword = $this->secretEncryptionService->decrypt(
            $certificate->pfxPasswordEncrypted()
        );

        if ($decryptedPassword === null || $decryptedPassword ==='')
        {
            $this->logRepository->error(
                channel: 'certificate',
                message: 'No fue posible desencriptar la contraseña del certificado de emisión.',
                context: [
                    'company_id' => $companyId,
                    'certificate_id' => $certificate->id(),
                ],
                companyId: $companyId,
                code: 'CERTIFICATE_PASSWORD_NOT_DECRYPTED',
            );

            throw new RuntimeException(
                'No fue posible descifrar la clave del certificado digital de la empresa.'
            );
        }

        $this->logRepository->info(
            channel: 'certificate',
            message: 'Certificado de emisión cargvado correctamente para la empresa.',
            context: [
                'company_id' => $companyId,
                'certificate_id' => $certificate->id(),
                'serial_number' => $certificate->serialNumber(),
                'current_validity_status' => $certificate->currentValidityStatus(),
            ],
            companyId: $companyId,
            code: 'CERTIFICATE_LOADED_FOR_EMISSION',
        );

        return new EmissionCertificateContextDto(
            certificateId: (int)  $certificate->id(),
            companyId:$certificate->companyId(),
            alias: $certificate->alias(),
            pfxPath: $certificate->pfxPath(),
            pfxPasswordDecrypted: $decryptedPassword,
            pfxContents: $pfxContents,
            serialNumber: $certificate->serialNumber(),
            subjectName: $certificate->subjectName(),
            issuerName: $certificate->issuerName(),
            validFrom: $certificate->validFrom(),
            validTo: $certificate->validTo(),
            currentValidityStatus: $certificate->currentValidityStatus(),
        );
    }
}
