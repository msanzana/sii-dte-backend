<?php
namespace App\Console\Commands;

use App\Modules\Dte\Application\Services\EmitCertificateNoticeService;
use App\Modules\Dte\Application\Services\SyncDefaultCertificateForCompanyService;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCertificateRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshCompanyCertificateDefaultsComand extends Command
{
    protected $sigtnature = 'dte:refresh-company-certificate-defaults';
    protected $desccription = 'Recalcula vigencia y certificado default por empresa';
    public function __construct(
        private readonly SiiCertificateRepositoryInterface $siiCertificateRepository,
        private readonly SyncDefaultCertificateForCompanyService $syncDefaultCertificateForCompanyService,
        private readonly EmitCertificateNoticeService $emitCertificateNoticeService,
    ){
        parent::__construct();
    }

    public function handle():int
    {
        $companyIds = DB::table('sii_certificates')
            ->distinct()
            ->pluck('company_id')
            ->map(fn ($id) => (int)$id)
            ->all();
        foreach ($companyIds as $companyId)
        {
            $beforeDefault = $this->siiCertificateRepository->findDefaultByCompanyId($companyId);
            $afterDefault = $this->syncDefaultCertificateForCompanyService->execute($companyId);

            if($beforeDefault && !$afterDefault)
            {
                $this->emitCertificateNoticeService->automatic(
                    companyId: $companyId,
                    certificateId: (int) $beforeDefault->id(),
                    type: 'warning',
                    code: 'default_certificate_lost',
                    title: 'Se perdió el certificado default vigente',
                    message: 'La empresa dejó de tener un certificado vigente utilizable para la emisión de DTE.'
                );
            }

            if(
                (!$beforeDefault && $afterDefault) ||
                ($beforeDefault && $afterDefault && $beforeDefault->id() !== $afterDefault->id())
            )
            {
                $this->emitCertificateNoticeService->automatic(
                    companyId: $companyId,
                    certificateId: (int) $afterDefault->id(),
                    type: 'info',
                    code: 'default_certificate_updated',
                    title: 'Certificado default actualizado',
                    message: 'El sistema actualizó automáticamente el certificado default vigente de la empresa.'
                );
            }
        }

        $this->info('Revisión de certificados finalizada correctamente.');

        return self::SUCCESS;
    }
}
