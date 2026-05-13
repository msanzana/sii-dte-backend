<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\City;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CityEloquentModel;

final class CityPersistenceMapper
{
    public function toDomain(CityEloquentModel $model): City
    {
        return new City(
            id: $model->id,
            comuneId: $model->comune_id,
            name: $model->name,
            code: $model->code,
            isActive: (bool) $model->is_active,
        );
    }
}
