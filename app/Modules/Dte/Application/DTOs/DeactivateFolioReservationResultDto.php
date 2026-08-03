<?php
namespace App\Modules\Dte\Application\DTOs;
final class DeactivateFolioReservationResultDto
{
    public function __construct(
        public readonly int $reservationId,
        public readonly bool $deactivated,
        public readonly int $expiredFolioDetails,
        public readonly int $unaffectedFolioDetails,
        public readonly string $source,
        public readonly string $message,
    ){}
}