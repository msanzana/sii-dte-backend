<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
use App\Models\Dte\Domain\Entities\Company;
interface CompanyRepositoryInterface
{
    public function create(Company $company): Company;
    public function update(Company $company): Company;
    public function findById(int $id): ?Company;
    public function findByRut(string $rut): ?Company;
    public function existsActiveById(int $id): bool;
}
