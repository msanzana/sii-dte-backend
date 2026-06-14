<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
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
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:auth_permissions,id'],
        ];
    }
}
