<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\DTOs\EmissionCertificateMaterialContextDto;
use App\Modules\Dte\Application\Services\LoadCertificateForEmisionService;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Infrastructure\Crypto\CertificateMaterialExtractorService;

final class LoadCertificateMaterialForEmisionService
{
    public function __construct(
        private readonly LoadCertificateForEmisionService $loadCertificateForEmisionService,
        private readonly CertificateMaterialExtractorService $certificateMaterialExtractorService,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    ){}

    public function execute(int $companyId): EmissionCertificateMaterialContextDto
    {
        $certificateContext = $this->loadCertificateForEmisionService->execute($companyId);

        $material = $this->certificateMaterialExtractorService->extractFromRawMaterial(
            pfxContents: $certificateContext->pfxContents,
            password: $certificateContext->pfxPasswordDecrypted
        );

        $this->logRepository->info(
            channel: 'certificate',
            message: 'Material criptográfico del certificado ',
            context: [
                'company_id' => $companyId,
                'certificate_id' => $certificateContext->certificateId,
                'serial_number' => $certificateContext->serialNumber,
                'currect_vqalidity_status' => $certificateContext->currentValidityStatus,
            ],
            companyId: $companyId,
            code: 'CERTIFICATE_MATERIAL_READY_FOR_EMISSION'
        );

        return new EmissionCertificateMaterialContextDto(
            certificateId: $certificateContext->certificateId,
            companyId: $certificateContext->companyId,
            alias: $certificateContext->alias,
            pfxPath: $certificateContext->pfxPath,
            serialNumber: $certificateContext->serialNumber,
            subjectName: $certificateContext->subjectName,
            issuerName: $certificateContext->issuerName,
            validFrom: $certificateContext->validFrom,
            validTo: $certificateContext->validTo,
            currentValidityStatus: $certificateContext->currentValidityStatus,
            privateKeyPem: $material['private_key_pem'],
            certificatePem: $material['certificate_pem'],
            certificateBase64: $material['certificate_base64'],
            modulusBase64: $material['modulus_base64'],
            exponentBase64: $material['exponent_base64']
        );
    }
}
