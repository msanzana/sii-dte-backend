<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DteDocumentResource extends JsonResource
{
    public function toArray(Request $request):array{
        return [
            'id' => $this->resource->id,
            'external_id' => $this->resource->external_id,
            'status' => $this->resource->status,
            'dte_type' => $this->resource->dteType,
            'issue_date' => $this->resource->issueDate,
            'receiver_city_id' => $this->resource->receiverCityId,
            'net_amount' => $this->resource->netAmount,
            'exempt_amount' => $this->resource->exemptAmount,
            'tax_amount' => $this->resource->taxAmount,
            'total_amount' => $this->resource->totalAmount,
        ];
    }
}
