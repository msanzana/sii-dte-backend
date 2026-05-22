<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreparedDteDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->resource->documentId,
            'external_id' => $this->resource->externalId,
            'company_id' => $this->resource->companyId,
            'dte_type' => $this->resource->dteType,
            'caf_id' => $this->resource->cafId,
            'folio' => $this->resource->folio,
            'caf_folio_start' => $this->resource->cafFolioStart,
            'caf_folio_end' => $this->resource->cafFolioEnd,
            'status' => $this->resource->status,
            'sii_environment' => $this->resource->siiEnvironment,
        ];
    }
}
