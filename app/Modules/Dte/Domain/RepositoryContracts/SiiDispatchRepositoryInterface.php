<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Exceptions\SiiDispatch;

interface SiiDispatchRepositoryInterface
{
    public function create(SiiDispatch $dispatch): SiiDispatch;
    public function update(SiiDispatch $dispatch): SiiDispatch;
    public function findById(int $id): ?SiiDispatch;
    public function findLatestByDocumentId(int $documentId): ?SiiDispatch;
}
