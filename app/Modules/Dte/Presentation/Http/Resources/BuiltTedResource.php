<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuiltTedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->resource->documentId,
            'external_id' => $this->resource->externalId,
            'company_id' => $this->resource->companyId,
            'dte_type' => $this->resource->dteType,
            'folio' => $this->resource->folio,
            'caf_id' => $this->resource->cafId,
            'status' => $this->resource->status,
            'unsigned_xml_path' => $this->resource->unsignedXmlPath,
            'ted_xml' => $this->resource->tedXml,
        ];
    }
}
