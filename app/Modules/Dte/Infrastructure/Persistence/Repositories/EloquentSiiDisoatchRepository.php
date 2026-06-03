<?php

namespace App\Modules\Dte\Infrastructure\Persistence\Repositories;

use App\Modules\Dte\Domain\Exceptions\SiiDispatch;
use App\Modules\Dte\Domain\RepositoryContracts\SiiDispatchRepositoryInterface;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiDispatchEloquentModel;
use App\Modules\Dte\Infrastructure\Persistence\Mappers\SiiDispatchPersistenceMapper;


final class EloquentSiiDispatchRepository implements SiiDispatchRepositoryInterface
{
    public function __construct(
        private readonly SiiDispatchPersistenceMapper $mapper,
    )
    {
    }


    public function create(SiiDispatch $dispatch): SiiDispatch
    {
        $model = new SiiDispatchEloquentModel();

        $model->fill([
            'batch_uuid' => $dispatch->batchUuid(),
            'company_id' => $dispatch->companyId(),
            'dte_document_id' => $dispatch->dteDocumentId(),
            'environment' => $dispatch->environment(),
            'transport_type' => $dispatch->transportType(),
            'status' => $dispatch->status(),
            'track_id' => $dispatch->trackId(),
            'request_identifier' => $dispatch->requestIdentifier(),
            'request_path' => $dispatch->requestPath(),
            'request_headers' => $dispatch->requestHeaders(),
            'request_body_path' => $dispatch->requestBodyPath(),
            'response_http_status' => $dispatch->responseHttpStatus(),
            'response_body' => $dispatch->responseBody(),
            'upload_status_code' => $dispatch->uploadStatusCode(),
            'upload_status_message' => $dispatch->uploadStatusMessage(),
            'retry_count' => $dispatch->retryCount(),
            'next_retry_at' => $dispatch->nextRetryAt(),
            'error_message' => $dispatch->errorMessage(),
            'sent_at' => $dispatch->sentAt(),
            'last_polled_at' => $dispatch->lastPolledAt(),
            'processed_at' => $dispatch->processedAt(),

        ]);

        $model->save();

        return $this->findById((int) $model->id);
    }

    public function update(SiiDispatch $dispatch): SiiDispatch
    {
        $model = SiiDispatchEloquentModel::query()->findOrFail($dispatch->id());

        $model->fill([
            'status' => $dispatch->status(),
            'track_id' => $dispatch->trackId(),
            'response_http_status' => $dispatch->responseHttpStatus(),
            'response_body' => $dispatch->responseBody(),
            'upload_status_code' => $dispatch->uploadStatusCode(),
            'upload_status_message' => $dispatch->uploadStatusMessage(),
            'error_message' => $dispatch->errorMessage(),
            'sent_at' => $dispatch->sentAt(),
            'last_polled_at' => $dispatch->lastPolledAt(),
            'processed_at' => $dispatch->processedAt(),
        ]);

        $model->save();

        return $this->findById((int) $model->id());
    }

    public function findById(int $id): ?SiiDispatch
    {
        $model = SiiDispatchEloquentModel::query()->finmd($id);

        if(!$model)
        {
            return null;
        }

        return $this->mapper->toDomain($model);
    }

    public function findLatestByDocumentId(int $documentId): ?SiiDispatch
    {
        $model = SiiDispatchEloquentModel::query()
                ->where('dte_document_id', $documentId)
                ->orderByDesc('id')
                ->first();
        if(!$model)
        {
            return null;
        }

        return $this->mapper->toDomain($model);
    }
}
