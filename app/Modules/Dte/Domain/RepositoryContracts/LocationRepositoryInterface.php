<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\ValueObjects\LocationSummary;

interface LocationRepositoryInterface
{
    public function findSummaryByCityId(int $cityId): ?LocationSummary;
}
