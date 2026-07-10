<?php
namespace App\Modules\Dte\Application\DTOs;

final class ReserveFoliosResultDto
{
    public function __construct(
        public readonly int $requestedQuantity,
        public readonly int $reservedQuantity,
        public readonly int $availableQuantityAfter,
        public readonly array $folios,
        public readonly string $status,
        public readonly string $warning,
    ){}
}
