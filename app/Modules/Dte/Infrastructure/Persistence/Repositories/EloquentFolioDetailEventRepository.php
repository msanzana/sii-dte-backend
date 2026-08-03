<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailEventRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioDetailEventEloquentModel;

final class EloquentFolioDetailEventRepository implements FolioDetailEventRepositoryInterface
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
    ): void
    {
        FolioDetailEventEloquentModel::query()->create([
            'folio_detail_id' => $folioDetailId,
            'company_id' => $companyId,
            'external_system_id' => $externalSystemId,
            'branch_office_number' => $branchOfficeNumber,
            'facility_number' => $facilityNumber,
            'event_code' => $eventCode,
            'from_status_code' => $fromStatusCode,
            'to_status_code' => $toStatusCode,
            'message' => $message,
            'user_id' => $userId,
            'payload_json' => $payloadJson,
            'created_at' => now(),
        ]);
    }

    public function createBatch(array $events): void
    {
        if ($events === []) {
            return;
        }

        $now = now();

        foreach ($events as &$event) {
            $event['created_at'] =
                $event['created_at'] ?? $now;
        }

        unset($event);

        foreach (array_chunk($events, 500) as $chunk) {
            FolioDetailEventEloquentModel::query()
                ->insert($chunk);
        }
    }
}
