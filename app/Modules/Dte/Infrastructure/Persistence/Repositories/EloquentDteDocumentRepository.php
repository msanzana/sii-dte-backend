<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;
use App\Modules\Dte\Domain\Enums\DispatchStatus;
use App\Modules\Dte\Domain\Enums\DteStatus;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiDispatchEloquentModel;
use App\Modules\Dte\Domain\Entities\DteDocument;
use App\Modules\Dte\Domain\RepositoryContracts\DteDocumentRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteDocumentEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteLineItemEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\DteReferenceEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\DteDocumentPersistenceMapper;

final class EloquentDteDocumentRepository implements DteDocumentRepositoryInterface
{
    public function __construct(
        private readonly DteDocumentPersistenceMapper $mapper,
    ) {
    }

    public function create(DteDocument $document): DteDocument
    {
        $model = new DteDocumentEloquentModel();

        $model->fill([
            'external_id' => $document->externalId(),
            'company_id' => $document->companyId(),
            'dte_type' => $document->dteType()->value,
            'folio' => $document->folio(),
            'issue_date' => $document->issueDate(),
            'status' => $document->status(),
            'sii_environment' => $document->siiEnvironment() ?? config('dte.default_environment'),
            'receiver_document' => $document->receiver()->document(),
            'receiver_name' => $document->receiver()->name(),
            'receiver_giro' => $document->receiver()->giro(),
            'receiver_address' => $document->receiver()->address(),
            'receiver_city_id' => $document->receiver()->cityId(),
            'receiver_email' => $document->receiver()->email(),
            'net_amount' => $document->netAmount(),
            'exempt_amount' => $document->exemptAmount(),
            'tax_amount' => $document->taxAmount(),
            'total_amount' => $document->totalAmount(),
            'header_payload' => $document->headerPayload(),
            'totals_payload' => $document->totalsPayload(),
            'raw_input' => $document->rawInput(),
            'unsigned_xml_path' => $document->unsignedXmlPath(),
            'signed_xml_path' => $document->signedXmlPath(),
            'ted_xml' => $document->tedXml(),
            'last_error_code' => $document->lastErrorCode(),
            'last_error_message' => $document->lastErrorMessage(),
            'queued_at' => $document->queuedAt(),
            'sent_at' => $document->sentAt(),
            'accepted_at' => $document->acceptedAt(),
            'rejected_at' => $document->rejectedAt(),
            // 🟩 NUEVO
            'automation_retry_action' => $document->automationRetryAction(),
            'automation_retry_count' => $document->automationRetryCount(),
            'automation_next_retry_at' => $document->automationNextRetryAt(),

            'external_system_id' => $document->externalSystemId(),
            'caf_id' => $document->cafId(),
            'folio_reservation_id' => $document->folioReservationId(),
            'branch_office_number' => $document->branchOfficeNumber(),
            'facility_number' => $document->facilityNumber(),
            'external_branch_code' => $document->externalBranchCode(),
        ]);

        $model->save();

        foreach ($document->items() as $item) {
            DteLineItemEloquentModel::query()->create([
                'dte_document_id' => $model->id,
                'line_number' => $item->lineNumber(),
                'item_code_type' => $item->itemCodeType(),
                'item_code' => $item->itemCode(),
                'name' => $item->name(),
                'description' => $item->description(),
                'quantity' => $item->quantity(),
                'unit_price' => $item->unitPrice(),
                'discount_percent' => $item->discountPercent(),
                'discount_amount' => $item->discountAmount(),
                'tax_exempt' => $item->taxExempt(),
                'line_amount' => $item->lineAmount(),
                'extra_payload' => $item->extraPayload(),
            ]);
        }

        foreach ($document->references() as $reference) {
            DteReferenceEloquentModel::query()->create([
                'dte_document_id' => $model->id,
                'line_number' => $reference->lineNumber(),
                'referenced_dte_type' => $reference->referencedDteType(),
                'referenced_folio' => $reference->referencedFolio(),
                'referenced_issue_date' => $reference->referencedIssueDate(),
                'reference_code' => $reference->referenceCode(),
                'reason' => $reference->reason(),
                'extra_payload' => $reference->extraPayload(),
            ]);
        }

        return $this->findById((int) $model->id);
    }

    public function update(DteDocument $document): DteDocument
    {
        $model = DteDocumentEloquentModel::query()->findOrFail($document->id());

        $model->fill([
            'folio' => $document->folio(),
            'status' => $document->status(),
            'sii_environment' => $document->siiEnvironment() ?? config('dte.default_environment'),
            'receiver_city_id' => $document->receiver()->cityId(),
            'net_amount' => $document->netAmount(),
            'exempt_amount' => $document->exemptAmount(),
            'tax_amount' => $document->taxAmount(),
            'total_amount' => $document->totalAmount(),
            'header_payload' => $document->headerPayload(),
            'totals_payload' => $document->totalsPayload(),
            'raw_input' => $document->rawInput(),
            'unsigned_xml_path' => $document->unsignedXmlPath(),
            'signed_xml_path' => $document->signedXmlPath(),
            'ted_xml' => $document->tedXml(),
            'last_error_code' => $document->lastErrorCode(),
            'last_error_message' => $document->lastErrorMessage(),
                // NUEVO
            'automation_retry_action' =>
                $document->automationRetryAction(),

            // NUEVO
            'automation_retry_count' =>
                $document->automationRetryCount(),

            // NUEVO
            'automation_next_retry_at' =>
                $document->automationNextRetryAt(),

            'queued_at' => $document->queuedAt(),
            'sent_at' => $document->sentAt(),
            'accepted_at' => $document->acceptedAt(),
            'rejected_at' => $document->rejectedAt(),
        ]);

        $model->save();

        return $this->findById((int) $model->id);
    }

    public function findById(int $id): ?DteDocument
    {
        $model = DteDocumentEloquentModel::query()
            ->with(['items', 'references'])
            ->find($id);

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findByIdForUpdate(int $id): ?DteDocument
    {
        $model = DteDocumentEloquentModel::query()
            ->with(['items', 'references'])
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findByExternalId(string $externalId): ?DteDocument
    {
        $model = DteDocumentEloquentModel::query()
            ->with(['items', 'references'])
            ->where('external_id', $externalId)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findPendingForDispatch(int $limit = 100): array
    {
        return DteDocumentEloquentModel::query()
            ->with(['items', 'references'])
            ->whereIn('status', ['signed', 'queued'])
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (DteDocumentEloquentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function findIdsByStatuses(array $statuses, int $limit = 100): array
    {
        return DteDocumentEloquentModel::query()
            ->whereIn('status', $statuses)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
    public function findIdsEligibleForAutomation(
        array $statuses,
        int $limit = 100
    ): array
    {
        $documentsTable =
            (new DteDocumentEloquentModel())->getTable();

        $dispatchesTable =
            (new SiiDispatchEloquentModel())->getTable();

        $maxRetryAttempts = (int) config(
            'dte.automation.dispatch_retry.max_attempts',
            3
        );
        return DteDocumentEloquentModel::query()
            ->whereIn(
                "{$documentsTable}.status",
                $statuses
            )
            ->where(function ($query) use (
                $documentsTable,
                $dispatchesTable,
                $maxRetryAttempts
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Estados distintos de SIGNED
                |--------------------------------------------------------------------------
                |
                | Continúan siendo elegibles según la lista solicitada por el pump.
                |
                */

                $query
                    ->where(
                        "{$documentsTable}.status",
                        '!=',
                        DteStatus::SIGNED->value
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Documentos SIGNED
                    |--------------------------------------------------------------------------
                    |
                    | Un SIGNED sigue siendo elegible salvo que SU ÚLTIMO dispatch
                    | haya terminado explícitamente UPLOAD_REJECTED.
                    |
                    | No bloqueamos FAILED aquí todavía:
                    | esa política será tratada mediante retry_count / next_retry_at.
                    |
                    */
                    ->orWhere(function ($signedQuery) use (
                        $documentsTable,
                        $dispatchesTable,
                        $maxRetryAttempts
                    ): void {

                        $signedQuery
                            ->where(
                                "{$documentsTable}.status",
                                DteStatus::SIGNED->value
                            )
                            ->whereNotExists(
                                function ($blockingDispatchQuery) use (
                                    $documentsTable,
                                    $dispatchesTable,
                                    $maxRetryAttempts
                                ): void {

                                    $blockingDispatchQuery
                                        ->selectRaw('1')
                                        ->from(
                                            "{$dispatchesTable} as blocking_dispatch"
                                        )
                                        ->whereColumn(
                                            'blocking_dispatch.dte_document_id',
                                            "{$documentsTable}.id"
                                        )
                                        ->where(function ($statusQuery) use (
                                            $maxRetryAttempts
                                        ): void {

                                            $statusQuery
                                                ->where(
                                                    'blocking_dispatch.status',
                                                    DispatchStatus::UPLOAD_REJECTED->value
                                                )

                                                ->orWhere(function ($failedQuery) use (
                                                    $maxRetryAttempts
                                                ): void {

                                                    $failedQuery
                                                        ->where(
                                                            'blocking_dispatch.status',
                                                            DispatchStatus::FAILED->value
                                                        )

                                                        /*
                                                        | Un FAILED sólo deja de bloquear cuando
                                                        | representa explícitamente un retry programado,
                                                        | su plazo venció y el contador es válido.
                                                        */
                                                        ->where(function ($notRetryableQuery) use (
                                                            $maxRetryAttempts
                                                        ): void {

                                                            $notRetryableQuery
                                                                ->whereNull(
                                                                    'blocking_dispatch.next_retry_at'
                                                                )

                                                                ->orWhere(
                                                                    'blocking_dispatch.next_retry_at',
                                                                    '>',
                                                                    now()
                                                                )

                                                                ->orWhere(
                                                                    'blocking_dispatch.retry_count',
                                                                    '<',
                                                                    1
                                                                )

                                                                ->orWhere(
                                                                    'blocking_dispatch.retry_count',
                                                                    '>',
                                                                    $maxRetryAttempts
                                                                );
                                                        });
                                                });
                                        })

                                        /*
                                        | Sólo bloquea si este UPLOAD_REJECTED
                                        | es realmente el ÚLTIMO dispatch.
                                        */
                                        ->whereNotExists(
                                            function ($newerDispatchQuery) use (
                                                $dispatchesTable
                                            ): void {

                                                $newerDispatchQuery
                                                    ->selectRaw('1')
                                                    ->from(
                                                        "{$dispatchesTable} as newer_dispatch"
                                                    )
                                                    ->whereColumn(
                                                        'newer_dispatch.dte_document_id',
                                                        'blocking_dispatch.dte_document_id'
                                                    )
                                                    ->whereColumn(
                                                        'newer_dispatch.id',
                                                        '>',
                                                        'blocking_dispatch.id'
                                                    );
                                            }
                                        );
                                }
                            );
                    });
            })
                        /*
            |--------------------------------------------------------------------------
            | Retry interno del documento
            |--------------------------------------------------------------------------
            |
            | Un documento es elegible en uno de dos casos:
            |
            | 1. No tiene ningún retry interno programado.
            |
            | 2. Tiene un retry interno válido y su fecha ya venció.
            |
            | Un retry futuro o incompleto debe quedar fuera del pump.
            |
            */

            ->where(function ($automationRetryQuery) use (
                $documentsTable
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Documento sin retry interno
                |--------------------------------------------------------------------------
                |
                | Para considerarlo realmente "sin retry" exigimos que los
                | tres componentes estén en su estado inicial:
                |
                | action = null
                | count  = 0
                | next   = null
                |
                */

                $automationRetryQuery
                    ->where(function ($noRetryQuery) use (
                        $documentsTable
                    ): void {

                        $noRetryQuery
                            ->whereNull(
                                "{$documentsTable}.automation_retry_action"
                            )
                            ->where(
                                "{$documentsTable}.automation_retry_count",
                                0
                            )
                            ->whereNull(
                                "{$documentsTable}.automation_next_retry_at"
                            );
                    })

                    /*
                    |--------------------------------------------------------------------------
                    | Retry interno programado y ya vencido
                    |--------------------------------------------------------------------------
                    */

                    ->orWhere(function ($dueRetryQuery) use ($documentsTable): void {
                    $maxAttempts = (int) config(
                        'dte.automation.document_retry.max_attempts',
                        3
                    );

                    $dueRetryQuery
                        ->where(
                            "{$documentsTable}.automation_retry_count",
                            '>=',
                            1
                        )
                        ->where(
                            "{$documentsTable}.automation_retry_count",
                            '<=',
                            $maxAttempts
                        )
                        ->whereNotNull(
                            "{$documentsTable}.automation_next_retry_at"
                        )
                        ->where(
                            "{$documentsTable}.automation_next_retry_at",
                            '<=',
                            now()
                        )
                        ->where(
                            function ($stageQuery) use ($documentsTable): void {
                                $stageQuery
                                    /*
                                    |--------------------------------------------------------------------------
                                    | FOLIO_ASSIGNED -> build_xml
                                    |--------------------------------------------------------------------------
                                    */
                                    ->where(
                                        function ($query) use ($documentsTable): void {
                                            $query
                                                ->where(
                                                    "{$documentsTable}.status",
                                                    DteStatus::FOLIO_ASSIGNED->value
                                                )
                                                ->where(
                                                    "{$documentsTable}.automation_retry_action",
                                                    'build_xml'
                                                );
                                        }
                                    )

                                    /*
                                    |--------------------------------------------------------------------------
                                    | NEEDS_RESEND -> build_xml
                                    |--------------------------------------------------------------------------
                                    */
                                    ->orWhere(
                                        function ($query) use ($documentsTable): void {
                                            $query
                                                ->where(
                                                    "{$documentsTable}.status",
                                                    DteStatus::NEEDS_RESEND->value
                                                )
                                                ->where(
                                                    "{$documentsTable}.automation_retry_action",
                                                    'build_xml'
                                                );
                                        }
                                    )

                                    /*
                                    |--------------------------------------------------------------------------
                                    | XML_BUILT -> build_ted
                                    |--------------------------------------------------------------------------
                                    */
                                    ->orWhere(
                                        function ($query) use ($documentsTable): void {
                                            $query
                                                ->where(
                                                    "{$documentsTable}.status",
                                                    DteStatus::XML_BUILT->value
                                                )
                                                ->where(
                                                    "{$documentsTable}.automation_retry_action",
                                                    'build_ted'
                                                );
                                        }
                                    )

                                    /*
                                    |--------------------------------------------------------------------------
                                    | TED_BUILT -> sign_xml
                                    |--------------------------------------------------------------------------
                                    */
                                    ->orWhere(
                                        function ($query) use ($documentsTable): void {
                                            $query
                                                ->where(
                                                    "{$documentsTable}.status",
                                                    DteStatus::TED_BUILT->value
                                                )
                                                ->where(
                                                    "{$documentsTable}.automation_retry_action",
                                                    'sign_xml'
                                                );
                                        }
                                    );
                            }
                        );
                });
            })
            ->orderBy(
                "{$documentsTable}.id"
            )
            ->limit($limit)
            ->pluck(
                "{$documentsTable}.id"
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->all();
    }
}
