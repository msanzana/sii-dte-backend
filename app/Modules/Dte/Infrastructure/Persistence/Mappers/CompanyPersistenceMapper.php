<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Models\Dte\Domain\Entities\Company;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\CompanyEloquentModel;

final class CompanyPersistenceMapper
{
    public function toDomain(CompanyEloquentModel $model): Company
    {
        return new Company(
            id: $model->id,
            rut: $model->rut,
            rutBody: $model->rut_body,
            rutDv: $model->rut_dv,
            legalName: $model->legal_name,
            tradeName: $model->trade_name,
            giro: $model->giro,
            address: $model->address,
            cityId: $model->city_id,
            dteEmail: $model->dte_email,
            resolutionNumber: $model->resolution_number,
            resolutionDate: $model->resolution_date?->format('Y-m-d'),
            siiEnvironment: $model->sii_environment,
            isActive: (bool) $model->is_active,
        );
    }
}
