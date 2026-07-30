<?php

namespace App\Packages\Admin\Roles\Requests;

use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = data_get(userObject(), 'organization.id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Role::class, 'name')->where(function ($query) use ($organizationId) {
                    return $organizationId
                        ? $query->where('organization_id', $organizationId)
                        : $query->whereNull('organization_id');
                }),
            ],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'scope' => ['sometimes', 'nullable', 'string', Rule::in(['global', 'organization'])],
        ];
    }
}
