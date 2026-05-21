<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request):array
    {
        return [
            'id'=> $this->resource->id,
            'company_id' => $this->resource->companyId,
            'alias' => $this->resource->alias,
            'pfx_path' => $this->resource->pfxPath,
            'serial_number' => $this->resource->serialNumber,
            'subject_name' => $this->resource->subjectName,
            'issuer_name' => $this->resource->resourceName,
            'valid_from' => $this->resource->validFrom,
            'valid_to' => $this->resource->validTo,
            'is_default' => $this->resource->isDefault,
            'is_active' => $this->resource->isActive,
        ];
    }
}
