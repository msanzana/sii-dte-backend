<?php
namespace App\Modules\Dte\Application\DTOs;
final class SignDteXmlInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
