<?php
namespace App\Modules\Dte\Application\DTOs;
final class PaginateResultDto
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $lastPage,
    ){}
}
