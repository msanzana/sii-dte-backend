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

    /**
     * Cada elemento debe contener los campos de folio_detail_events.
     *
     * @param array<int, array<string, mixed>> $events
     */
    public function createBatch(array $events): void;
}
