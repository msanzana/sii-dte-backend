<?php
namespace App\Modules\Dte\Presentation\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ReleaseReservedFolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return ['external_system_id' => ['required', 'integer'],
                'sii_document_type_code' => ['required', 'string', 'max:20'],
                'folio_number' => ['required', 'integer', 'min:1'],
                'branch_office_number' => ['nullable', 'integer', 'min:0'],
                'facility_number' => ['nullable', 'integer', 'min:0'],
                'external_branch_code' => ['nullable', 'string', 'max:100'],
                'reason' => ['nullable', 'string']];
                }
    }
