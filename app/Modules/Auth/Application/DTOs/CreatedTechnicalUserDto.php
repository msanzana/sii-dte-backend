<?php

namespace App\Modules\Auth\Application\DTOs;

/**
 * Contiene una credencial de un solo uso de visualizacion.
 * NO serializar en logs, colas, eventos ni historiales de API.
 */
final class CreatedTechnicalUserDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $companyId,
        public readonly int $externalSystemId,
        public readonly string $loginUuid,
        public readonly string $plainPassword,
    ) {
    }
}
