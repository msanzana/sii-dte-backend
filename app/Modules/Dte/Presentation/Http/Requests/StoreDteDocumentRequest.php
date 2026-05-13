<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDteDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules():array
    {
        return [
            'country_id' => ['required', 'integer', 'exists:companies,id'],
            'external_id' => ['nullable', 'uuid'],
            'dte_type' => ['required','integer', Rule::in([33,34,39,41,46,52,56,61])],
            'issue_date' => ['required', 'date'],

            'receiver.document' => ['required','string','max:20'],
            'receiver.name' => ['required','string','max:120'],
            'receiver.giro' => ['nullable','string','max:150'],
            'receiver.address' => ['nullable','string','max:150'],
            'receiver.city_id' => ['nullable','integer','exists:cities,id'],
            'receiver.email' => ['nullable','email','max:150'],

            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.item_code_type' => ['nullable', 'string', 'max:20'],
            'items.*.item_code' =>  ['nullable', 'string', 'max:50'],
            'items.*.name' => ['required','string','max:120'],
            'items.*.description' => ['nullable','string'],
            'items.*.quantity' => ['required', 'numeric','gt:0'],
            'items.*.unit_price' => ['required','numeric','min:0'],
            'items.*.discount_percent' => ['nullable','numeric','min:0','max:100'],
            'items.*.discount_amount' => ['nullable','numeric','min:0'],
            'items.*.tax_exempt' => ['nullable','boolean'],
            'items.*.extra_payload' => ['nullable', 'array'],

            'references' => ['nullable','array'],
            'references.*.referenced_dte_type' => ['nullable','integer'],
            'references.*.referenced_folio' => ['nullable','integer','min:1'],
            'references.*.referenced_issue_date' => ['nullable','date'],
            'references.*.referenced_code' => ['nullable', 'integer'],
            'references.*.reason' => ['nullable','string', 'max:100'],
            'referenced.*.extra_payload' => ['nullable','array'],

            'header_payload' => ['nullable','array'],

        ];
    }
}
