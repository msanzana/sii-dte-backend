<?php
namespace App\Modules\Auth\Application\DTOs;

final class ListAccessibleCompaniesResultDto
{
    public function __construct(
        public readonly int $userId,
        public readonly array $companies
    ){}
}

