<?php
namespace App\Modules\Dte\Application\DTOs;
final class FolioStatusItemDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $sortOrder,
        public readonly bool $isActive,
    ){}
}
