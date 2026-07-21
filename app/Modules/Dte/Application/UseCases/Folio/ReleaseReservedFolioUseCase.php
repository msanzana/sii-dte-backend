<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\ReleaseReservedFolioInputDto;
use App\Modules\Dte\Application\DTOs\ReleaseReservedFolioResultDto;
use App\Modules\Dte\Application\Services\ReleaseReservedFolioService;
use App\Modules\Dte\Application\Services\ValidateExternalSystemAccessService;

final class ReleaseReservedFolioUseCase
{
    public function __construct(
        private readonly ValidateExternalSystemAccessService $validateExternalSystemAccessService,
        private readonly ReleaseReservedFolioService $releaseReservedFolioService,
    ){}

    public function execute(ReleaseReservedFolioInputDto $input): ReleaseReservedFolioResultDto
    {
        $this->validateExternalSystemAccessService->execute(
            companyId: $input->companyId,
            externalSystemId: $input->externalSystemId,
        );

        return $this->releaseReservedFolioService->execute($input);
    }
}
