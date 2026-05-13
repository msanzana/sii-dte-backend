<?php
namespace App\Modules\Dte\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'paused' => $this->resource->paused,
            'reason' => $this->resource->reason,
        ];
    }
}
