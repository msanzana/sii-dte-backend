<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules():array
    {
        return [
            'company_id' => ['required', 'integer','exists:companies,id'],
            'alias' => ['required','string','max:100'],
            'password' => ['required', 'string', 'max:255'],
            'pfx_file' => ['required', 'file', 'mimes:pfx,p12'],
        ];
    }
}
