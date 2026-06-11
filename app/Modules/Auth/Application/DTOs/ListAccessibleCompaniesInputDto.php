<?php

namespace App\Modules\Auth\Application\DTOs;

class ListAccessibleCompaniesInputDto
{
    public function __construct(
        public readonly string $bearerToken,
    ) {
    }
}
