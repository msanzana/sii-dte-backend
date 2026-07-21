<?php
namespace App\Modules\Dte\Application\UseCases\ExternalSystem;

use App\Modules\Dte\Application\DTOs\ExternalSystemItemDto;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;

final class ListExternalSystemUseCase
{
    public function __construct(
        private readonly ExternalSystemRepositoryInterface $externalSystemRepository
    ){}

    public function execute(int $companyId): array
    {
        $items = $this->externalSystemRepository->findByCompanyId($companyId);

        return array_map(
            fn ($item) => new ExternalSystemItemDto(
                id: (int) $item->id(),
                companyId: $item->companyId(),
                code: $item->code(),
                name: $item->name(),
                description: $item->description(),
                isActive: $item->isActive(),
            ),$items
        );
    }
}
