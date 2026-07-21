<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ExternalSystemEloquentModel;

final class ExternalSystemPersistenceMapper
{
    public function toDomain(
        ExternalSystemEloquentModel $model
    ): ExternalSystem
    {
        return new ExternalSystem(
            id: (int) $model->id,
            companyId: (int) $model->company_id,
            code: (string) $model->code,
            name: (string) $model->name,
            description: $model->description,
            isActive: (bool) $model->is_acive,
        );
    }
}