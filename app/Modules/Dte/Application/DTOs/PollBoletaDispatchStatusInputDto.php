<?php
namespace App\Modules\Dte\Application\DTOs;

final class PollBoletaDispatchStatusInputDto
{
    public function __construct(
        public readonly int $dispatchId,
    )
    {}
}
