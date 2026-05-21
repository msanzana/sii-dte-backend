<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\SiiCaf;
use App\Modules\Dte\Domain\ValueObjects\ReservedFolio;

interface SiiCafRepositoryInterface
{
    public function create(SiiCaf $caf): SiiCaf;

    public function findById(int $id): ?SiiCaf;

    /**
     * @return SiiCaf[]
     */
    public function findActiveByCompanyAndType(int $companyId, int $dteType): array;

    public function existsOverlappingRange(
        int $companyId,
        int $dteType,
        int $folioStart,
        int $folioEnd
    ): bool;

    public function reserveNextAvailableFolio(int $companyId, int $dteType): ReservedFolio;

}
