<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateExternalSystemInputDto
{
    public function __construct(
        public readonly int $companyId,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $descripcion,
        public readonly bool $isActive = true
    )
    {}
}
