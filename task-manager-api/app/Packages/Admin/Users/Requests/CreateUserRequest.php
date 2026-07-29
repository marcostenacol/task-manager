<?php

namespace App\Packages\Admin\Users\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'uuid', Rule::exists(Role::class, 'id')],
            'organization_id' => ['sometimes', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
