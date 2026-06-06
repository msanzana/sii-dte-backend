<?php
namespace App\Modules\Dte\Application\DTOs;

final class PollSiiUploadStatusInputDto
{
    public function __construct(
        public readonly int $dispatchId,
    )
    {}
}
