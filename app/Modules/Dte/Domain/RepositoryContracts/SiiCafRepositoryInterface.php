<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCaf;

interface DteRepositoryInterface
{
    public function create(SiiCaf $caf): SiiCaf;
    public function findById(int $id): ?SiiCaf;
    public function findByCompanyId(int $companyId): array;
    public function findActiveByCompanyIdAndDteType(int $companyId, int $dteType): ?array;
    public function existsOverlappingTange(
        int $companyId,
        int $dateType,
        int $folioStart,
        int $folioEnd
    ): bool;

}
