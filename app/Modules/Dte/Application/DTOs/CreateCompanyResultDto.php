<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateCompanyResultDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $rut,
        public readonly string $legalName,
        public readonly int $cityId,
        public readonly string $siiEnvironment,
        public readonly bool $isActive,
    )
    {}
}
