<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Models\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\CompanyPersistenceMapper;

final class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly CompanyPersistenceMapper $mapper,
    ){}
    public function findById(int $id): ?Company
    {
        $model = CompanyEloquentModel::query()->find($id);
        if (!$model){
            return null;
        }
        return $this->mapper->toDomain($model);
    }

    public function existsActiveById(int $id):bool
    {
        return CompanyEloquentModel::query()
                ->where('id', $id)
                ->where('is_active',true)
                ->exists();
    }
}
