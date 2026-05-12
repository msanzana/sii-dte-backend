<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\DteDocument;


interface DteDocumentRepositoryInterface
{
    public function create(DteDocument $document): DteDocument;
    public function update(DteDocument $document): DteDocument;
    public function findById(int $id): ?DteDocument;
    public function findByExternalId(string $externalId): DteDocument;
    public function findPendingForDispatch(int $limit = 100): array;
}
