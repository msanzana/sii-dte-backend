<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserCompanyAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'access_id' => $this->route('accessId'),
        ]);
    }

    public function rules(): array
    {
        return [
            'access_id' => ['required', 'integer', 'exists:auth_user_company_accesses,id'],
            'is_default' => ['required', 'boolean'],
            'can_select_company' => ['required', 'boolean'],
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:auth_roles,id'],
        ];
    }
}
