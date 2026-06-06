<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class SiiDispatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'dispatch_id' => $this->resource->dispatchId,
            'document_id' => $this->resource->documentId ?? null,
            'batch_uuid' => $this->resource->batchUuid ?? null,
            'status' => $this->resource->status,
            'track_id' => $this->resource->trackId,
            'upload_status_code' => $this->resource->uploadStatusCode,
            'upload_status_message' => $this->resource->uploadStatusMessage,
            'request_body_path' => $this->resource->requestBodyPath ?? null,
            'request_body' => $this->resource->requestBody ?? null,
        ];
    }
}
