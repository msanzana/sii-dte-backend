<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\RepositoryContracts\DteTypeAuthorizationRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentDteTypeAuthorizationRepository
    implements DteTypeAuthorizationRepositoryInterface
{
    public function existsActiveTypeByCode(int $dteType): bool
    {
        return DB::table('dte_document_types')
            ->where('code', $dteType)
            ->where('is_active', true)
            ->exists();
    }

    public function isAuthorizedForCompany(
        int $companyId,
        int $dteType
    ): bool {
        return DB::table('dte_type_authorizations as a')
            ->join(
                'dte_document_types as t',
                't.id',
                '=',
                'a.dte_document_type_id'
            )
            ->where('a.company_id', $companyId)
            ->whereNull('a.external_system_id')
            ->where('a.is_active', true)
            ->where('t.code', $dteType)
            ->where('t.is_active', true)
            ->exists();
    }

    public function isAuthorizedForSystem(
        int $companyId,
        int $externalSystemId,
        int $dteType
    ): bool {
        return DB::table('dte_type_authorizations as a')
            ->join(
                'dte_document_types as t',
                't.id',
                '=',
                'a.dte_document_type_id'
            )
            ->where('a.company_id', $companyId)
            ->where('a.external_system_id', $externalSystemId)
            ->where('a.is_active', true)
            ->where('t.code', $dteType)
            ->where('t.is_active', true)
            ->exists();
    }
}