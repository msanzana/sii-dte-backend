<?php
namespace App\Modules\Dte\Application\UseCases\Document;
final class SignDteXmlInputDto
{
    public function __construct(
        public readonly int $documentId,
    )
    {}
}
