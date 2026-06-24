<?php
namespace App\Modules\Dte\Application\UseCases\Company;

use App\Modules\Dte\Application\DTOs\CreateCompanyInputDto;
use App\Modules\Dte\Application\DTOs\CreateCompanyResultDto;
use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\Exceptions\CityNotFoundException;
use App\Modules\Dte\Domain\Exceptions\DuplicateCompanyRutException;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\IntegrationLogRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class CreateCompanyUseCase
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly IntegrationLogRepositoryInterface $logRepository,
    )
    {}

    public function execute(CreateCompanyInputDto $input): CreateCompanyResultDto
    {
        if($this->companyRepository->existsByRut($input->rut))
        {
            throw DuplicateCompanyRutException::withRut($input->rut);
        }

        if(!$this->cityRepository->existsActiveById($input->cityId))
        {
            throw CityNotFoundException::withId($input->cityId);
        }

        return DB::transaction(function () use ($input) {
            $company = new Company(
                id: null,
                rut: $input->rut,
                rutBody: $input->rutBody,
                rutDv: $input->rutDv,
                legalName: $input->legalName,
                tradeName: $input->tradeName,
                giro: $input->giro,
                address: $input->address,
                cityId: $input->cityId,
                dteEmail: $input->dteEmail,
                resolutionNumber: $input->resolutionNumber,
                resolutionDate: $input->resolutionDate,
                siiEnvironment: $input->siiEnvironment,
                isActive: $input->isActive,
            );
            $saved = $this->companyRepository->create($company);

            $this->logRepository->info(
                channel: 'company',
                message: 'Empresa registrada correctamente.',
                context: [
                    'company_id' => $saved->id(),
                    'rut' => $saved->rut(),
                ],
                companyId: $saved->id(),
                code: 'COMPANY_CREATED'
            );
            return new CreateCompanyResultDto(
                id: $saved->id(),
                rut: $company->rut(),
                legalName: $company->legalName(),
                cityId: $company->cityId(),
                siiEnvironment: $company->siiEnvironment(),
                isActive: $company->isActive(),
            );
        });
    }
}
