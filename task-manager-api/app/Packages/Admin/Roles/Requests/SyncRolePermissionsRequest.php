<?php

namespace App\Packages\Admin\Roles\Requests;

use App\Packages\Admin\Permissions\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['uuid', Rule::exists(Permission::class, 'id')],
        ];
    }
}
