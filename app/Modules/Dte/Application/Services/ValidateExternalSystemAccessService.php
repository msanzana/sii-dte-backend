<?php
namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use RuntimeException;

final class ValidateExternalSystemAccessService
{
    public function __construct(
        private readonly ExternalSystemRepositoryInterface $externalSystemRepository
    ){}
    public function execute(int $companyId, int $externalSystemId): ExternalSystem
    {
        error_log($companyId);
        error_log($externalSystemId);
        $externalSystem = $this->externalSystemRepository->findByCompanyAndId(
            $companyId,
            $externalSystemId
        );

        if(!$externalSystem) {
            throw new RuntimeException('El sistema externo no existe o no pertenece a la empresa seleccionada.');
        }
    
        if(!$externalSystem->isActive())
        {
            throw new RuntimeException('El sistema externo está inactivo y no puede operar con folios.');
        }

        return $externalSystem;
    }
}
