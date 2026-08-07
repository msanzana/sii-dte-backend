<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
final class ListFolioReservationDetailsRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_code' => ['nullable','string','max:50'],
            'reserved' => ['nullable','boolean'],
            'folio_from' => ['nullable','integer','min:1'],
            'folio_to' => ['nullable','integer','gte:folio_from'],
            'page' => ['nullable','integer','min:1'],
            'per_page'=>['nullable','integer','min:1','max:200'],
        ];
    }
}