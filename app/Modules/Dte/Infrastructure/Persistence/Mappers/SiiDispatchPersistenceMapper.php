<?php
namespace App\Modules\Dte\Infrastructure\Persistence\Mappers;

use App\Modules\Dte\Domain\Entities\SiiDispatch;
use App\Modules\Dte\Infrastructure\Persistence\EloquentModels\SiiDispatchEloquentModel;

final class SiiDispatchPersistenceMapper
{
    public function toDomain(SiiDispatchEloquentModel $model): SiiDispatch
    {
        return new SiiDispatch(
            id: $model->id,
            batchUuid: $model->batch_uuid,
            companyId: $model->company_id,
            dteDocumentId: $model->dte_document_id,
            environment: $model->environment,
            transportType: $model->transport_type,
            status: $model->status,
            trackId: $model->track_id,
            requestIdentifier: $model->request_identifier,
            requestPath: $model->request_path,
            requestHeaders: $model->request_headers,
            requestBodyPath: $model->request_body_path,
            responseHttpStatus: $model->response_http_status,
            responseBody: $model->response_body,
            uploadStatusCode: $model->upload_status_code,
            uploadStatusMessage: $model->upload_status_message,
            retryCount: (int) $model->retry_count,
            nextRetryAt: $model->next_retry_at?->format('Y-m-d H:i:s'),
            errorMessage: $model->error_message,
            sentAt: $model->sent_at?->format('Y-m-d H:i:s'),
            lastPolledAt: $model->last_polled_at?->format('Y-m-d H:i:s'),
            processedAt: $model->processed_at?->format('Y-m-d H:i:s'),
        );
    }
}
