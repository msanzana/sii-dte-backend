<?php

namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadCompanyLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'mimetypes:image/png,image/jpeg',
                'max:2048',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' =>
                'Debe adjuntar el logo de la empresa.',

            'logo.file' =>
                'El logo debe ser un archivo válido.',

            'logo.mimetypes' =>
                'El logo debe ser una imagen PNG o JPEG.',

            'logo.max' =>
                'El logo no puede superar los 2 MB.',

            'logo.dimensions' =>
                'El logo no puede superar 4096x4096 píxeles.',
        ];
    }
}