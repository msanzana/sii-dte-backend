<?php
namespace  App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindAvailableFoliosRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'external_system_id' => ['required', 'integer'],
            'sii_document_type_code' => ['required','string','max:20'],
            'breach_office_number' => ['nullable','integer','min:0'],
            'facility_number' => ['nullable','integer','min:0'],
            'external_branch_code' => ['nullable','string','max:100'],
            'preview_limit' => ['nullable','integer','min:1','max:200'],
        ];
    }
}