<?php
namespace App\Modules\Auth\Infrastructure\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required','email'],
            'password' => ['required','string','min:6'],
        ];
    }

}
