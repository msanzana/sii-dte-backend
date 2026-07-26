<?php

namespace App\Modules\Dte\Application\Services;

use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Domain\RepositoryContracts\SiiCafRepositoryInterface;
use RuntimeException;

final class RecalculateCafCountersService
{
    public function __construct(
        private readonly SiiCafRepositoryInterface $cafRepository,
        private readonly FolioDetailRepositoryInterface $folioDetailRepository,
    ) {
    }

    public function execute(int $cafId): void
    {
        $caf = $this->cafRepository->findById($cafId);

        if (!$caf) {
            throw new RuntimeException(
                "No existe el CAF {$cafId} para recalcular sus contadores."
            );
        }

        $totalAuthorizedFolios = max(
            0,
            $caf->folioEnd() - $caf->folioStart() + 1
        );

        $reservedFolios = $this->folioDetailRepository
            ->countReservedByCafId($cafId);

        $usedFolios = $this->folioDetailRepository
            ->countUsedByCafId($cafId);

        $availableFolios = max(
            0,
            $totalAuthorizedFolios
                - $reservedFolios
                - $usedFolios
        );

        $this->cafRepository->updateOperationalFolioCounters(
            cafId: $cafId,
            availableFoliosCount: $availableFolios,
            reservedFoliosCount: $reservedFolios,
            usedFoliosCount: $usedFolios
        );
    }

}
