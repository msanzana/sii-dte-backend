<?php
namespace App\Modules\Dte\Application\DTOs;
final class PauseServiceInputDto
{
    public function __construct(
        public readonly bool $paused,
        public readonly ?string $reason = null,
    )
    {}
}
