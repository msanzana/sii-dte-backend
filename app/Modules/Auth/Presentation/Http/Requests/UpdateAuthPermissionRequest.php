<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permission_id' => $this->route('permissionId'),
        ]);
    }

    public function rules(): array
    {
        return [
            'permission_id' => ['required', 'integer', 'exists:auth_permissions,id'],
            'code' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
