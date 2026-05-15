<?php
namespace App\Modules\Dte\Application\DTOs;
final class CreateCompanyInputDto
{
    public function __construct(
        public readonly string $rut,
        public readonly string $legalName,
        public readonly ?string $tradeName,
        public readonly ?string $giro,
        public readonly string $address,
        public readonly int $cityId,
        public readonly ?string $dteEmail,
        public readonly ?string $resolutionNumber,
        public readonly ?string $resolutionDate,
        public readonly string $siiEnvironment,
        public readonly bool $isActive = true,
    ){}
}
