<?php
namespace App\Modules\Dte\Application\DTOs;
final class BuildDteXmlInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
