<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Repositories;

use App\Modules\Auth\Domain\Entities\TechnicalUserSystemAccess;
use App\Modules\Auth\Domain\RepositoryContracts\TechnicalUserSystemAccessRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentTechnicalUserSystemAccessRepository implements TechnicalUserSystemAccessRepositoryInterface
{
    public function grant(
        int $userId,
        int $companyId,
        int $externalSystemId
    ): void {
        DB::table('auth_user_external_system_accesses')->insert([
            'user_id' => $userId,
            'company_id' => $companyId,
            'external_system_id' => $externalSystemId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findActiveByUserId(int $userId): array
    {
        return DB::table('auth_user_external_system_accesses')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(
                static fn (object $row): TechnicalUserSystemAccess =>
                    new TechnicalUserSystemAccess(
                        id: (int) $row->id,
                        userId: (int) $row->user_id,
                        companyId: (int) $row->company_id,
                        externalSystemId: (int) $row->external_system_id,
                        isActive: (bool) $row->is_active,
                    )
            )
            ->all();
    }
}
