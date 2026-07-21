<?php
namespace App\Modules\Dte\Application\DTOs;
final class ReleaseReservedFolioResultDto
{
    public function __construct(
        public readonly int $folioNumber,
        public readonly bool $released,
        public readonly string $newStatus,
        public readonly string $message
    ){}
}
