<?php

namespace App\Modules\Dte\Domain\RepositoryContracts;

use App\Modules\Dte\Domain\Entities\DtePrintConfiguration;

interface DtePrintConfigurationRepositoryInterface
{
    public function findByDteTypeCode(
        int $dteType
    ): ?DtePrintConfiguration;
}