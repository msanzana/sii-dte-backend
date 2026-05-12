<?php
namespace App\Modules\Dte\Application\UseCases\ServiceState;

use App\Modules\Dte\Application\DTOs\PauseServiceInputDto;
use App\Modules\Dte\Application\DTOs\ServiceStateResultDto;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;

final class PauseDteServiceUseCase
{
    public function __construct(
        private readonly SystemSettingRepositoryInterface $settingRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    )
    {}
    public function execute(PauseServiceInputDto $input): ServiceStateResultDto
    {
        $this->settingRepository->set(
            config('dte.service_state.paused_key'),
            true,
            'Indica si el servicio DTE está pausado'
        );
        $this->settingRepository->set(
            config('dte.service_state.pause_reason_key'),
            [
                'reason' => $input->reason,
                'at' => now()->toDateTimeString(),
            ],
            'Razón de pausa del servicio DTE'
        );
        $this->logRepository->warning(
            channel: 'system',
            message: 'Servicio DTE pausado manualmente.',
            context: ['reason' => $input->reason],
            code: 'DTE_SERVICE_PAUSED'
        );

        return new ServiceStateResultDto(
            paused: true,
            reason: $input->reason,
        );
    }
}
