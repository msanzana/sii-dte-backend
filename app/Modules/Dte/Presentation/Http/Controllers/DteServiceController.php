<?php
namespace App\Modules\Dte\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dte\Application\DTOs\PauseServiceInputDto;
use App\Modules\Dte\Application\DTOs\ServiceStateResultDto;
use App\Modules\Dte\Application\UseCases\ServiceState\PauseDteServiceUseCase;
use App\Modules\Dte\Application\UseCases\ServiceState\ResumeDteServiceUseCase;
use App\Modules\Dte\Domain\RepositoryContracts\SystemSettingRepositoryInterface;
use App\Modules\Dte\Presentation\Http\Requests\PauseDteServiceRequest;
use App\Modules\Dte\Presentation\Http\Resources\ServiceStateResource;
use Illuminate\Http\JsonResponse;

class DteServiceController extends Controller
{
    public function __construct(
        private readonly PauseDteServiceUseCase $pauseUseCase,
        private readonly ResumeDteServiceUseCase $resumeUseCase,
        private readonly SystemSettingRepositoryInterface $settingRepository,

    )
    {}

    public function show(): JsonResponse
    {
        $paused = (bool) $this->settingRepository->get(
            config('dte.service_state.paused_key'),
            false
        );

        $reasonData = $this->settingRepository->get(
            config('dte.service_state.pause_reason_key'),
            null
        );

        $reason = is_array($reasonData)
            ? ($reasonData['reason'] ?? null)
            : null;

        return response()->json([
            'data' => new ServiceStateResource(new ServiceStateResultDto(
                paused: $paused,
                reason: $reason
            ))
        ]);
    }
    public function pause(PauseDteServiceRequest $request): JsonResponse
    {
        $result = $this->pauseUseCase->execute(
            new PauseServiceInputDto(
                reason: $request->validated('reason')
            )
        );
        return response()->json([
            'message' => 'Servicio pausado.',
            'data' => new ServiceStateResource($result),
        ]);
    }
    public function resume(): JsonResponse
    {
        $result = $this->resumeUseCase->execute();
        return response()->json([
            'message' => 'Servicio reanudado.',
            'data' => new ServiceStateResource($result),
        ]);
    }
}
