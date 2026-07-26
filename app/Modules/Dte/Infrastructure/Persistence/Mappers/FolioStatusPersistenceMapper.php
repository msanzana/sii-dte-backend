<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\FolioStatus;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioStatusEloquentModel;

final class FolioStatusPersistenceMapper
{
    public function toDomain(
        FolioStatusEloquentModel $model
    ): FolioStatus {
        return new FolioStatus(
            id: (int) $model->id,
            code: (string) $model->code,
            name: (string) $model->name,
            description: $model->description,
            sortOrder: (int) $model->sort_order,
            isActive: (bool) $model->is_active,
        );
    }
}