<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
use App\Models\Dte\Domain\Entities\Company;
interface CompanyRepositoryInterface
{
    public function findById(int $id): ?Company;
    public function existActiveById(int $id): bool;
}
