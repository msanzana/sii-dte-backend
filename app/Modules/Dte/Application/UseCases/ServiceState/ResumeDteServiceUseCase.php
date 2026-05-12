<?php
namespace App\Modules\Dte\Application\UseCases\ServiceState;

use App\Modules\Dte\Application\DTOs\ServiceStateResultDto;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;


final class ResumeDteServiceUseCase
{
    public function __construct(
        private readonly SystemSettingRepositoryInterface $SettingRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    )
    {}
    public function execute(): ServiceStateResultDto
    {
        $this->SettingRepository->set(
            config('dte.service_state.paused_key'),
            false,
            'Indica si el servicio DTE esta pausado'
        );

        $this->SettingRepository->set(
            config('dte.service_state.pause_reason_key'),
            null,
            'Razón de pausa del servicio DTE'
        );
        $this->logRepository->info(
            channel:'system',
            message:'Servicio DTE reanudado manualmente.',
            code: 'DTE_SERVICE_RESUMED'
        );
        return new ServiceStateResultDto(
            paused: false,
            reason: null,
        );
    }
}
