<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuiltDteXmlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->resource->documentId,
            'external_id' => $this->resource->externalId,
            'company_id' => $this->resource->companyId,
            'dte_type' => $this->resource->dteType,
            'folio' => $this->resource->folio,
            'document_xml_id' => $this->resource->documentXmlId,
            'status' => $this->resource->status,
            'sii_environment' => $this->resource->siiEnvironment,
            'unsigned_xml_path' => $this->resource->unsignedXmlPath,
        ];
    }
}
