<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoletaDispatchResource extends JsonResource
{
    public function toArray(Request $request):array
    {
        return [
            'dispatch_id' => $this->resource->dispatchId,
            'document_id' => $this->resource->documentId ?? null,
            'batch_uuid' => $this->resource->batchUuid ?? null,
            'status' => $this->resource->status,
            'track_id' => $this->resource->trackId,
            'send_status_code' => $this->resource->sendStatusCode,
            'send_status_message' => $this->resource->sendStatusMessage,
            'request_body_path' => $this->resource->requestBodyPath ?? null,
            'raw_body' => $this->resource->rawBody ?? null,
        ];
    }
}
