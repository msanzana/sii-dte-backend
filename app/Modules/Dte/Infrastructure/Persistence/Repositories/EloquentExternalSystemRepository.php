<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\ExternalSystem;
use App\Modules\Dte\Domain\RepositoryContracts\ExternalSystemRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\ExternalSystemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\ExternalSystemPersistenceMapper;
use RuntimeException;

final class EloquentExternalSystemRepository implements ExternalSystemRepositoryInterface
{
    public function __construct(
        private readonly ExternalSystemPersistenceMapper $mapper,
    ){}

    public function create(ExternalSystem $externalSystem): ExternalSystem
    {
        $model = ExternalSystemEloquentModel::query()->create([
            'company_id' => $externalSystem->companyId(),
            'code' => trim($externalSystem->code()),
            'name' => trim($externalSystem->name()),
            'description' => $externalSystem->description(),
            'is_active' => $externalSystem->isActive(),
        ]);

        return $this->mapper->toDomain($model);
    }

    public function update(ExternalSystem $externalSystem): ExternalSystem
    {
        if($externalSystem->id() === null)
        {
            throw new RuntimeException
            ('No es posible actualizar un sistema externo sin id.');
        }
        $model = ExternalSystemEloquentModel::query()
                ->where('id',$externalSystem->id())
                ->where('company_id', $externalSystem->companyId())
                ->first();
        if(!$model)
        {
            throw new RuntimeException
            ('El Sistema externo no existe o no pertenece a la empresa.');
        }
        $model->fill([
            'code' => trim($externalSystem->code()),
            'name' => trim($externalSystem->name()),
            'description' => $externalSystem->description(),
            'is_active' => $externalSystem->isActive(),
        ]);

        $model->save();

        return $this->mapper->toDomain($model->refresh());

    }

    public function findById(int $id): ?ExternalSystem
    {
        $model = ExternalSystemEloquentModel::query()->find($id);
        return $model
            ? $this->mapper->toDomain($model)
            : null;
    }

    public function findByCompanyAndId(int $companyId, int $id): ?ExternalSystem
    {

        $model = ExternalSystemEloquentModel::query()
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->first();
        return $model
            ? $this->mapper->toDomain($model)
            :null;
            
    }

    public function findByCompanyAndCode(int $companyId, string $code): ?ExternalSystem
    {
                $model = ExternalSystemEloquentModel::query()
                ->where('company_id', $companyId)
                ->where('code', trim($code))
                ->first();
        return $model
            ? $this->mapper->toDomain($model)
            :null;
    }

    public function findByCompanyId(int $companyId): array
    {
        return ExternalSystemEloquentModel::query()
                ->where('company_id',$companyId)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->map(
                    fn(ExternalSystemEloquentModel $model) =>
                    $this->mapper->toDomain($model)
                )
                ->all();

    }

    public function setActive(int $id, bool $isActive): void
    {
        ExternalSystemEloquentModel::qhery()
            ->where('id', $id)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now()
            ]);
    }
}