<?php
namespace App\Modules\Dte\Application\DTOs;
final class ListCompanyCertificateNoticesResultDto
{
    public function __construct(
        public readonly array $items,
    ){}
}
