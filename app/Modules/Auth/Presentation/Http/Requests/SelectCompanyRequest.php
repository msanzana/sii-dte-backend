<?php
namespace App\Modules\Auth\Infrastructure\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules():array
    {
        return [
            'company_id' => ['required','integer', 'exists:companies,id'],
        ];
    }
}
