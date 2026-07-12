<?php
namespace App\Modules\Dte\Application\DTOs;
final class AvailableFoliosResultDto
{
    public function __construct
    (
        public readonly int $availableQuantity,
        public readonly array $foliosPreview = [],
    ){}
}