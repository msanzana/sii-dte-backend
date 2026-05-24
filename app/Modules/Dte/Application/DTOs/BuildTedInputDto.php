<?php
namespace App\Modules\Dte\Application\DTOs;

final class BuildTedInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
