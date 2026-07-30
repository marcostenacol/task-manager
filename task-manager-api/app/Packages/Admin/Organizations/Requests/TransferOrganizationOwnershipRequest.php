<?php

namespace App\Packages\Admin\Organizations\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOrganizationOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_owner_user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
            'organization_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
