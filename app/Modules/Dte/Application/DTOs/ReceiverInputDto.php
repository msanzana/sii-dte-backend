<?php
namespace App\Modules\Dte\Application\DTOs;
final class ReceiverInputDto
{
    public function __construct(
        public readonly string $document,
        public readonly string $name,
        public readonly ?string $giro = null,
        public readonly ?string $address = null,
        public readonly ?int $cityId = null,
        public readonly ?string $email = null,
    ) {}
}
