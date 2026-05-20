<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'rut' => $this->resource->rut,
            'legal_name' => $this->resource->legalName,
            'city_id' => $this->resource->cityId,
            'sii_environment' => $this->resource->siiEnvironment,
            'is_active' => $this->resource->isActive,
        ];
    }

}
