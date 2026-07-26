<?php
namespace App\Modules\Dte\Application\UseCases\Folio;

use App\Modules\Dte\Application\DTOs\FolioStatusItemDto;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;

final class ListFolioStatusesUseCase
{
    public function __construct(
        private readonly FolioStatusRepositoryInterface $FolioStatusRepository
    ){}
    public function execute(): array
    {
        return array_map(
            fn($status) => new FolioStatusItemDto(
                id: $status->id(),
                code: $status->code(),
                name: $status->name(),
                description: $status->description(),
                sortOrder: $status->sortOrder(),
                isActive: $status->isActive()
            ),
            $this->FolioStatusRepository->findAllActive()
        );
    }
}
