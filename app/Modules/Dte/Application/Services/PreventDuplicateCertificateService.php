<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use RuntimeException;

final class PreventDuplicateCertificateService
{
    public function __construct(
        private readonly SiiCertificateRepositoryInterface $siiCertificateRepository
    ){}

    public function execute(
        int $companyId,
        ?string $pfxSha256,
        ?string $metadataHash
    ):void{
        $exists = $this->siiCertificateRepository->existsDuplicateByCompanyAndHashes(
            $companyId,
            $pfxSha256,
            $metadataHash
        );

        if($exists)
        {
            throw new RuntimeException(
                'Ya existe un certificado igual registrado para esta empresa.'
            );
        }
    }
}
