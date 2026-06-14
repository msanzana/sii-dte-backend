<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\ManagedCompany;
use App\Modules\Auth\Domain\RepositoryContracts\AuthCompanyStateRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentAuthCompanyStateRepository implements AuthCompanyStateRepositoryInterface
{
    public function findAll(?bool $isActive = null, int $limit = 100): array
    {
        $rows = DB::table('companies')
            ->when(
                $isActive !== null,
                fn ($query) => $query->where('is_active', $isActive)
            )
            ->orderBy('legal_name')
            ->limit($limit)
            ->select([
                'id',
                'rut',
                'legal_name',
                'is_active',
            ])
            ->get();

        return array_map(
            fn (object $row) => $this->toDomain($row),
            $rows->all()
        );
    }

    public function findById(int $companyId): ?ManagedCompany
    {
        $row = DB::table('companies')
            ->where('id', $companyId)
            ->select([
                'id',
                'rut',
                'legal_name',
                'is_active',
            ])
            ->first();

        return $row ? $this->toDomain($row) : null;
    }

    public function setActive(int $companyId, bool $isActive): void
    {
        DB::table('companies')
            ->where('id', $companyId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

    private function toDomain(object $row): ManagedCompany
    {
        return new ManagedCompany(
            id: (int) $row->id,
            rut: (string) $row->rut,
            legalName: (string) $row->legal_name,
            isActive: (bool) $row->is_active,
        );
    }
}
