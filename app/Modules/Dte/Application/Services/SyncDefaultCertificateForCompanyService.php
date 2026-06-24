<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use Carbon\CarbonImmutable;

final class SyncDefaultCertificateForCompanyService
{
    public function __construct(
        private readonly SiiCertificateRepositoryInterface $siiCertificateRepository,
    )
    {}

    public function execute(int $companyId, ?int $preferredCertificateId = null): ?SiiCertificate
    {
        $certificates = $this->siiCertificateRepository->findByCompanyId($companyId);

        foreach ($certificates as $certificate) {
            $this->siiCertificateRepository->updateValiditySnapshot(
                certificateId: $certificate->id(),
                lastValidityStatus: $certificate->currentValidityStatus(),
                lastValidityCheckAt: now()->format('Y-m-d H:i:s'),
            );
        }

        $validCertificates = array_values(array_filter(
            $certificates,
            fn(SiiCertificate $certificate) => $certificate->isCurrentlyValid()
        ));

        $this->siiCertificateRepository->clearDefaultByCompanyId($companyId);

        if($validCertificates ===[]){
            return null;
        }

        $selected = $this->resolvePreferredCertificate(
            $validCertificates,
            $preferredCertificateId
        );

        $this->siiCertificateRepository->setDefaultById($selected->id(), true);

        return $this->siiCertificateRepository->findById($selected->id());

    }

    private function resolvePreferredCertificate(array $validCertificates, ?int $preferredCertificateId):SiiCertificate
    {
        if($preferredCertificateId !== null)
        {
            foreach($validCertificates as $certificate)
            {
                if($certificate->id() === $preferredCertificateId)
                {
                    return $certificate;
                }
            }
        }

        usort($validCertificates, function (SiiCertificate $a, SiiCertificate $b ): int {
            $aValidTo = CarbonImmutable::parse($a->validTo());
            $bValidTo = CarbonImmutable::parse($b->validTo());

            if($aValidTo->equalTo($bValidTo))
            {
                return $b->id() <=> $a->id();
            }

            return $bValidTo->getTimestamp() <=> $aValidTo->getTimestamp();
        });

        return $validCertificates[0];
    }
}
