<?php

namespace App\Modules\Auth\Application\DTOs;

final class SelectCompanyInputDto
{
    public function __construct(
        public readonly string $bearerToken,
        public readonly int $companyId,
        public readonly ?string $userAgent,
        public readonly ?string $ipAddress,
    ) {
    }
}
