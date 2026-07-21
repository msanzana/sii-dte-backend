<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\ReserveFoliosInputDto;
use App\Modules\Dte\Application\DTOs\ReserveFoliosResultDto;
use App\Modules\Dte\Application\Services\ReserveFoliosService;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;

final class ReserveFoliosUseCase
{
    public function __construct(
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        private readonly ReserveFoliosService $reserveFoliosService,
    ){}

    public function execute(ReserveFoliosInputDto $input): ReserveFoliosResultDto
    {
        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
        );
        return $this->reserveFoliosService->execute($input);
    }
}
