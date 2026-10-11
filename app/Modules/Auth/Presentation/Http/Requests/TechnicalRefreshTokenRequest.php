<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TechnicalRefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }
}
