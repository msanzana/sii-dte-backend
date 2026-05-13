<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\City;
use App\Modules\Dte\Domain\RepositoryContracts\CityRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\CityPersistenceMapper;



final class EloquentCityRepository implements CityRepositoryInterface
{
    public function __construct(
        private readonly CityPersistenceMapper $mapper
    )
    {}

    public function findById(int $id): ?City
    {
        $model = CityEloquentModel::query()->find($id);
        if(!$model)
        {
            return null;
        }
        return $this->mapper->toDomain($model);
    }

    public function existsActiveById(int $id): bool
    {
        return CityEloquentModel::query()
              ->where('id', $id)
              ->where('is_active',true)
              ->exists();
    }
}
