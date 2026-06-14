<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_id' => $this->route('roleId'),
        ]);
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:auth_roles,id'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
