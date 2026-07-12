<?php
namespace App\Modules\Dte\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalSystemRequest extends FormRequest
{
    public function authorized():bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => ['required','string','max:100'],
            'name' => ['required','string','max:155'],
            'description' =>['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ];
    }


}