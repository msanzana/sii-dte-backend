<?php

namespace App\Modules\Dte\Application\Services;

final class RecalculateCafCountersService
{
    public function execute(int $cafId): void
    {
        /*
         * Este servicio queda declarado en este subbloque como parte del contrato funcional.
         * Su implementación real depende del repositorio Eloquent del siguiente subbloque.
         *
         * En el Subbloque 5.A.3 se conecta a:
         * - conteo de available
         * - conteo de reserved
         * - conteo de used
         * y update de sii_cafs
         */
    }
}
