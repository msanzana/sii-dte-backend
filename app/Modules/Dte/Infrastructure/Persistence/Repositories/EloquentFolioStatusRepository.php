<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\FolioStatus;
use App\Modules\Dte\Domain\RepositoryContracts\FolioStatusRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioStatusEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\FolioStatusPersistenceMapper;

final class EloquentFolioStatusRepository implements FolioStatusRepositoryInterface
{
    public function __construct(
        private readonly FolioStatusPersistenceMapper $mapper,
    ){}
    public function findById(int $id): ?FolioStatus
    {
        $model = FolioStatusEloquentModel::query()->find($id);
        return $model
            ? $this->mapper->toDomain($model)
            :null;
    }
    public function findByCode(string $code): ?FolioStatus
    {
        $model = FolioStatusEloquentModel::query()
                ->where('code', trim($code))
                ->where('is_active', true)
                ->first();
        return $model
            ? $this->mapper->toDomain($model)
            : null;
    }

    public function findAllActive(): array
    {
        return FolioStatusEloquentModel::query()
            ->where('is_active',true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(
                fn (FolioStatusEloquentModel $model) =>
                $this->mapper->toDomain($model)
            )
            ->all();
    }
}
