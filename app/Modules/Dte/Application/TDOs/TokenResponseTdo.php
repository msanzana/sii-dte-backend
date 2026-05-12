<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateDteDocumentResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $token = null,
        public readonly ?string $rawResponse = null,
        public readonly ?string $error = null,
    )
    {}
}
