<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignedDteXmlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->resource->documentId,
            'external_id' => $this->resource->externalId,
            'company_id' => $this->resource->companyId,
            'dte_type' => $this->resource->dteType,
            'folio' => $this->resource->folio,
            'certificate_id' => $this->resource->certificateId,
            'document_xml_id' => $this->resource->documentXmlId,
            'tmst_firma' => $this->resource->tmstFirma,
            'status' => $this->resource->status,
            'signed_xml_path' => $this->resource->signedXmlPath,
        ];
    }

}
