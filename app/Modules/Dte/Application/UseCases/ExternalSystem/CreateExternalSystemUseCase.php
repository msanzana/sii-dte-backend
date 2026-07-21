<?php
namespace App\Modules\Dte\Application\UseCases\ExternalSystem;

use App\Modules\Dte\Application\DTOs\CreateExternalSystemInputDto;
use App\Modules\Dte\Application\DTOs\ExternalSystemItemDto;
use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use RuntimeException;

final class CreateExternalSystemUseCase
{
    public function __construct(
        private readonly ExternalSystemRepositoryInterface $externalSysteRepository,
    ){}

    public function execute(CreateExternalSystemInputDto $input): ExternalSystemItemDto
    {
        $existing = $this->externalSysteRepository->findByCompanyAndCode(
            $input->companyId,
            $input->code
        );

        if($existing)
        {
            throw new RuntimeException('Ya existe un sistema externo con ese código en la empresa.');
        }

        $created = $this->externalSysteRepository->create(
            new ExternalSystem(
                id:null,
                companyId: $input->companyId,
                code: $input->code,
                name: $input->name,
                description: $input->description,
                isActive: $input->isActive,
            )
        );

        return new ExternalSystemItemDto(
            id: (int) $created->id(),
            companyId: $created->companyId(),
            code: $created->code(),
            name: $created->name(),
            description: $created->description(),
            isActive: $created->isActive(),
        );

    }
}
