<?php

namespace App\Packages\Admin\Users\Requests;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->route('id'))],
            'role_id' => ['sometimes', 'uuid', Rule::exists(Role::class, 'id')],
        ];
    }
}
