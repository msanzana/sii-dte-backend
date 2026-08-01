<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCafRequest extends FormRequest
{
    public function authorize():bool
    {
        return true;
    }
    public function rules():array
    {
        return [
            'external_system_id' => ['required','integer','exists:external_systems,id'],
            'caf_file' => ['required','file','mimetypes:text/xml,application/xml,text/plain'],
        ];
    }
}
