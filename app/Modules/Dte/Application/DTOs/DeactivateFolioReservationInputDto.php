<?php
namespace App\Modules\Dte\Application\DTOs;
final class DeactivateFolioReservationInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $reservationId,
        public readonly ?int $userId,
        public readonly string $reason,
        public readonly string $source,
    ){}
}