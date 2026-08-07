<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListFolioReservationsRequest extends FormRequest
{
    public function authorize():bool{
        return true;
    }

    public function rules():array
    {
        return [
            'external_system_id' => ['nullable','integer'],
            'sii_document_type_code' => ['nullable','string','max:20'],
            'branch_office_number' => ['nullable','integer','min:1'],
            'facility_number' => ['nullable','integer','min:1'],
            'is_active' => ['nullable','boolean'],
            'is_currently_valid' => ['nullable', 'boolean'],
            'page' => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1', 'max:200'],

        ];
    }
}