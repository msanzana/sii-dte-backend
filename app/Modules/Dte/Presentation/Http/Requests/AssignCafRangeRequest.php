<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignCafRangeRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }

    public function rules():array
    {
        return [
            'external_system_id' => ['required','integer','exists:external_systems,id',],
            'folio_range_from' => ['required','integer','min:1',],
            'folio_range_to' => ['required','integer','gte:folio_range_from',],
            'branch_office_number' => ['nullable','integer','min:1',],
            'facility_number' => ['nullable','integer','min:1',],
            'external_branch_code' => ['nullable','string','max:100',],
            'expires_at' => ['nullable','date','after:now',],
        ];
    }
}