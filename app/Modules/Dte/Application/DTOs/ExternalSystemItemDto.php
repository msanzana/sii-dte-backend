<?php
namespace App\Modules\Dte\Application\DTOs;
final class ExternalSystemItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isActive
    ){}
}
