<?php
namespace App\Modules\Dte\Domain\RepositoryContracts;
interface FolioDetailEventRepositoryInterface
{
    public function create(
        int $folioDetailId,
        int $companyId,
        ?int $externalSystemId,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        string $eventCode,
        ?string $fromStatusCode,
        ?string $toStatusCode,
        ?string $message,
        ?int $userId,
        ?string $payloadJson
    ): void;
}
