<?php

namespace App\Modules\Auth\Application\DTOs;

final class CurrentSessionInputDto
{
    public function __construct(
        public readonly string $bearerToken,
    ) {
    }
}
