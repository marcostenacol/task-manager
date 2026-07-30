<?php

namespace App\Packages\Admin\Organizations\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
            'role_id' => ['required', 'uuid', Rule::exists(Role::class, 'id')],
            'organization_id' => ['sometimes', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
