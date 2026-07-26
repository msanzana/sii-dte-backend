<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Entities\FolioDetail;
use App\Modules\Dte\Domain\RepositoryContracts\FolioDetailRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\FolioDetailEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\FolioDetailPersistenceMapper;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class EloquetFolioDepotRepository implements FolioDetailRepositoryInterface
{
    public function __construct(
        private readonly FolioDetailPersistenceMapper $mapper
    ){}

    public function createBranch(array $details): void
    {
        if($details === [])
        {
            return;
        }
        $now = now();
        $rowa = [];

        foreach($details as $detail)
        {
            if(!$detail instanceof FolioDetail)
            {
                throw new InvalidArgumentException(
                    'Todos los elementos deben ser instancias de FolioDetail.'
                );
            }
            $rows[] = [
                'folio_reservation_id' => $detail->folioReservationId(),
                'company_id' => $detail->companyId(),
                'external_system_id' => $detail->externalSystemId(),
                'caf_id' => $detail->cafId(),
                'sii_document_type_code' => $detail->siiDocumentTypeCode(),
                'branch_office_number' => $detail->branchOfficeNumber(),
                'facility_number' => $detail->facilityNumber(),
                'external_branch_code' => $detail->externalBranchCode(),
                'folio_number' => $detail->folioNumber(),
                'reserved' => $detail->reserved(),
                'reserved_at' => $detail->reservedAt(),
                'released_at' => $detail->releasedAt(),
                'used_at' => $detail->usedAt(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach(array_chunk($rows,500) as $chunk)
        {
            FolioDetailEloquentModel::query()->insert($chunk);
        }
    }

    public function findAvailableByFilters(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode,
        int $limit
    ): array {
        $query = FolioDetailEloquentModel::query()
                ->select('folio_details.*')
                ->join(
                    'folio_statuses','folio_statuses.id','=','folio_details.folio_status_id'
                )
                ->where('folio_details.company_id', $companyId)
                ->where('folio_details.external_system_id', $externalSystemId)
                ->where('folio_details.sii_document_type_code', $siiDocumentTypeCode)
                ->where('folio_statuses.code','available')
                ->where('folio_statuses.is_active', true)
                ->where('folio_details.reserved',false)
                ->whereNull('folio_details.dte_document_id')
                ->whereNull('folio_details.used_at');
        $this->applyExactDistributionFilters(
            query: $query,
            branchOfficeNumber: $branchOfficeNumber,
            facilityNumber: $facilityNumber,
            externalBranchCode: $externalBranchCode
        );

        return $query
            ->orderBy('folioDetails.folio_number')
            ->limit(max(1,$limit))
            ->lockForUpdate()
            ->get()
            ->map(
                fn (FolioDetailEloquentModel $model) =>
                $this->mapper->toDomain($model)
            )
            ->all();
    }

    public function findReservedReversibleByFilters(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        int $folioNumber,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ): ?FolioDetail {
        $query = FolioDetailEloquentModel::query()
        ->select('folio_details.*')
        ->join(
            'folio_statuses','folio_statuses.id','=','folio_details.folio_status_id'
        )
        ->where('folio_details.company_id', $companyId)
        ->where('folio_details.external_system_id', $externalSystemId)
        ->where('folio_details.sii_document_type_code', $siiDocumentTypeCode)
        ->where('folio_details.folio_number', $folioNumber)
        ->where('folio_statuses.code', 'reserved')
        ->where('folio_statuses.is_active', true)
        ->whereNull('folio_details.dte_document_id')
        ->whereNull('folio_details.used_at');
        $this->applyExactDistributionFilters(
            query: $query,
            branchOfficeNumber: $branchOfficeNumber,
            facilityNumber: $facilityNumber,
            externalBranchCode: $externalBranchCode
        );

        $model = $query->lockForUpdate()
                ->first();
        return $model
            ? $this->mapper->toDomain($model)
            : null;
    }

    public function updateReservationState(
        int $folioDetailId,
        int $folioStatusId,
        bool $reserved,
        ?string $reservedAt,
        ?string $releasedAt,
        ?string $usedAt
    ): void {
        FolioDetailEloquentModel::query()
            ->where('id', $folioDetailId)
            ->update([
                'folio_status_id' => $folioStatusId,
                'reserved' => $reserved,
                'reserved_at' => $reservedAt,
                'released_at' => $releasedAt,
                'used_at' => $usedAt,
                'updated_at' => now(),
            ]);
    }

    public function attachDocument(
        int $foplioDetailId,
        int $dteDocumentId,
        ?string $usedAt = null): void {
        FolioDetailEloquentModel::query()
            ->where('id', $foplioDetailId)
            ->update([
                'dte_document_id' => $dteDocumentId,
                'used_at' => $usedAt ?? now(),
                'reserved' => false,
                'updated_at' => now(),
            ]);

    }

    public function countAvailableByFilter(
        int $companyId,
        int $externalSystemId,
        string $siiDocumentTypeCode,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ): int {
        $query = FolioDetailEloquentModel::query()
            ->join(
                'folio_statuses', 'folio_statuses.id','=','folio_details.folio_status_id'
            )
            ->where('folio_details.company_id', $companyId)
            ->where('folio_details.external_system_id', $externalSystemId)
            ->where('folio_details.sii_document_type_code',$siiDocumentTypeCode)
            ->where('folio_statuses.code', 'available')
            ->where('folio_details.reserved', false)
            ->whereNull('folio_details.dte_document_id')
            ->whereNull('folio_details.used_at');

        $this->applyExactDistributionFilters(
            query: $query,
            branchOfficeNumber: $branchOfficeNumber,
            facilityNumber: $facilityNumber,
            externalBranchCode: $externalBranchCode
        );
        return $query->count('folio_details.id');
    }

    public function countReservedByCafId(int $cafId): int
    {
        return FolioDetailEloquentModel::query()
            ->join(
                'folio_statuses', 'folio_statuses.id','=','folio_details.folio_status_id'
            )
            ->where('folio_details.caf_id', $cafId)
            ->where('folio_stastuses.code','reserved')
            ->where('folio_detail.reserved',true)
            ->whereNull('folio_details.dte_document_id')
            ->count('folio_details.id');
    }

    public function countUsedByCafId(int $cafId): int
    {
        return FolioDetailEloquentModel::query()
            ->where('caf_id',$cafId)
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('dte_document_id')
                    ->orWhereNotNull('used_at');
            })
            ->count('id');
    }

    private function applyExactDistributionFilters(
        Builder $query,
        ?int $branchOfficeNumber,
        ?int $facilityNumber,
        ?string $externalBranchCode
    ): void {
        if ($branchOfficeNumber === null) {
            $query->whereNull(
                'folio_details.branch_office_number'
            );
        } else {
            $query->where(
                'folio_details.branch_office_number',
                $branchOfficeNumber
            );
        }

        if ($facilityNumber === null) {
            $query->whereNull(
                'folio_details.facility_number'
            );
        } else {
            $query->where(
                'folio_details.facility_number',
                $facilityNumber
            );
        }

        if ($externalBranchCode === null) {
            $query->whereNull(
                'folio_details.external_branch_code'
            );
        } else {
            $query->where(
                'folio_details.external_branch_code',
                $externalBranchCode
            );
        }
    }
}
