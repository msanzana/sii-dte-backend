<?php

namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'alias' => ['required', 'string', 'max:100'],
            'pfx_password' => ['required', 'string', 'max:255'],
            'pfx_file' => ['required', 'file', 'max:5120', 'extensions:pfx,p12'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'empresa',
            'alias' => 'alias',
            'pfx_password' => 'contraseña del certificado',
            'pfx_file' => 'archivo certificado PFX/P12',
            'is_active' => 'estado activo',
        ];
    }
}
