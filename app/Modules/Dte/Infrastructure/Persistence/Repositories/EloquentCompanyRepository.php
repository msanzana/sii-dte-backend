<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;


use App\Modules\Dte\Domain\Entities\Company;
use App\Modules\Dte\Domain\RepositoryContracts\CompanyRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\CompanyPersistenceMapper;

final class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(
        private readonly CompanyPersistenceMapper $mapper,
    ) {}
    public function create(Company $company): Company
    {
        $model = new CompanyEloquentModel;
        $model->fill([
            'rut' => $company->rut(),
            'rut_body' => $company->rutBody(),
            'rut_dv' => $company->rutDv(),
            'legal_name' => $company->legalName(),
            'trade_name' => $company->tradeName(),
            'giro' => $company->giro(),
            'address' => $company->address(),
            'city_id' => $company->cityId(),
            'dte_email' => $company->dteEmail(),
            'resolution_number' => $company->resolutionNumber(),
            'resolution_date' => $company->resolutioNDate(),
            'sii_environment' => $company->siiEnvironment(),
            'is_active' => $company->isActive(),
        ]);
        $model->save();

        return $this->findById((int) $model->id);
    }
    public function update(company $company): Company
    {
        $model = CompanyEloquentModel::query()->findOrFail($company->id());
        $model->fill([
            'trade_name' => $company->tradeName(),
            'giro' => $company->giro(),
            'address' => $company->address(),
            'city_id' => $company->cityId(),
            'dte_email' => $company->dteEmail(),
            'resolution_number' => $company->resolutionNumber(),
            'resolution_date' => $company->resolutioNDate(),
            'sii_environment' => $company->siiEnvironment(),
            'is_active' => $company->isActive(),
        ]);
        $model->save();

        return $this->findById((int) $model->id);
    }
    public function findById(int $id): ?Company
    {
        $model = CompanyEloquentModel::query()->find($id);
        if (!$model) {
            return null;
        }
        return $this->mapper->toDomain($model);
    }

    public function existsActiveById(int $id): bool
    {
        return CompanyEloquentModel::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->exists();
    }
    public function existsByRut(string $rut): bool
    {
        return CompanyEloquentModel::query()
            ->where('rut', $rut)
            ->exists();
    }

    public function findByRut(string $rut): ?Company
    {
        $model = CompanyEloquentModel::query()
                ->where('rut', $rut)
                ->first();
        if(!$model)
        {
            return null;
        }
        return $this->mapper->toDomain($model);
    }
}
