<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules():array
    {
        return [
            "rut"=> ['required','string','max:12'],
            'rut_body' => ['required','string','max:8'],
            'rut_dv' => ['required','string','max:1'],
            'legal_name' => ['required','string','max:120'],
            'trade_name' => ['required','string','max:120'],
            'giro' => ['required','string','max:150'],
            'address' => ['required','string','max:150'],
            'sii_activity_code' => ['required','string','max:10',],
            'city_id' => ['required','integer','exists:cities,id'],
            'dte_email'=> ['required','string','max:150'],
            'resolution_number' => ['required','string','max:20'],
            'resolution_date' => ['required','string'],
            'sii_environment' => ['required',Rule::in(['cert', 'prod'])],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
