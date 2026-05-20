<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCaf;

interface SiiCafRepositoryInterface
{
    public function create(SiiCaf $caf): SiiCaf;
    public function findById(int $id): ?SiiCaf;
    public function findActiveByCompanyIdAndDteType(int $companyId, int $dteType): ?array;
    public function existsOverlappingTange(
        int $companyId,
        int $dteType,
        int $folioStart,
        int $folioEnd
    ): bool;

}
