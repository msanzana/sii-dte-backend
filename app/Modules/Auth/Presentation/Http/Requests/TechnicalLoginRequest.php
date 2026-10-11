<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TechnicalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_uuid' => ['required', 'uuid'],
            'password' => ['required', 'string', 'min:32', 'max:255'],
        ];
    }
}
