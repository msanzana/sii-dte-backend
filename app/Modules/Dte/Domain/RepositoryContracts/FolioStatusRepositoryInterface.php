<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\FolioStatus;

interface FolioStatusRepositoryInterface
{
    public function findById(int $id): ?FolioStatus;
    public function findByCode(string $code): ? FolioStatus;
    public function findAllActive(): array;
}
