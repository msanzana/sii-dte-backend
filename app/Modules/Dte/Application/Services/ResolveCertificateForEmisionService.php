<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Application\Services\EmitCertificateNoticeService;
use App\Modules\Dte\Domain\Entities\SiiCertificate;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use RuntimeException;

final class ResolveCertificateForEmisionService
{
    public function __construct(
        private readonly SiiCertificateRepositoryInterface $siiCertificateRepository,
        private readonly EmitCertificateNoticeService $emitCertificateNoticeService,
    ){}

    public function execute(int $companyId): SiiCertificate
    {
        $certificate = $this->siiCertificateRepository->findDefaultValidByCompanyId($companyId);

        if(!$certificate)
        {
            $this->emitCertificateNoticeService->automatic(
                companyId: $companyId,
                certificateId: null,
                type: 'error',
                code: 'default_certificate_missing_for_emission',
                title: 'No existe un certificado vigente',
                message: 'No es posible generar o firmar DTE porque la empresa no tiene un certificado vigente.'
            );

            throw new RuntimeException(
                'No existe un certificado vigente pata la empresa seleccionada.'
            );
        }
        return $certificate;
    }
}
