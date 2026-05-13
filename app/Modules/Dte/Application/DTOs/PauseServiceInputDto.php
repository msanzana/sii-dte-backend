<?php
namespace App\Modules\Dte\Application\DTOs;
final class PauseServiceInputDto
{
    public function __construct(
        public readonly string $reason,
    )
    {}
}
