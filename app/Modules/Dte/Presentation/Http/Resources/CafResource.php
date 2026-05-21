<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CafResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->resource->id,
            'company_id' => $this->resource->companyId,
            'dte_type'=> $this->resource->dteType,
            'folio_start' => $this->resource->folioStart,
            'folio_end' => $this->resource->folioEnd,
            'authorized_at' => $this->resource->authorizedAt,
            'caf_xml_path' => $this->resource->cafXmlPath,
            'is_active' => $this->resource->isActive,
        ];
    }
}
