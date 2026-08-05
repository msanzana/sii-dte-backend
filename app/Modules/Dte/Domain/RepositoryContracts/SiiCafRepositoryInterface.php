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

    public function findActiveContainingFolio(
        int $companyId,
        int $dteType,
        int $folio
    ): ?SiiCaf;
    public function updateOperationalFolioCounters(
        int $cafId,
        int $availableFoliosCount,
        int $reservedFoliosCount,
        int $usedFoliosCount
    ): void;
    public function findByIdForUpdate(int $id): ?SiiCaf;
    public function assignExternalSystem(
        int $cafId,
        int $externalSystemId
    ): void;
    /**
     * @return int[]
     */
    public function findIdsForCounterReconciliation(
        int $afterId,
        int $limit
    ): array;
}
