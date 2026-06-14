<?php

namespace App\Modules\Auth\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:auth_users,id'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'is_default' => ['required', 'boolean'],
            'can_select_company' => ['required', 'boolean'],
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:auth_roles,id'],
        ];
    }
}
