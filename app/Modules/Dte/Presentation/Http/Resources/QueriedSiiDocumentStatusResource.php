<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueriedSiiDocumentStatusResource extends JsonResource
{
    public function toArray(Request $request):array
    {
        return [
            'document_id' => $this->resource->documentId,
            'external_id' => $this->resource->externalId,
            'company_id' => $this->resource->companyId,
            'dte_type' => $this->resource->dteType,
            'folio' => $this->resource->folio,
            'queried_via' => $this->resource->queriedVia,
            'sii_status_code' => $this->resource->siiStatusCode,
            'sii_status_message' => $this->resource->siiStatusMessage,
            'attention_number' => $this->resource->attentionNumber,
            'internal_status' => $this->resource->internalStatus,
            'raw_body' => $this->resource->rawBody,

        ];
    }
}
