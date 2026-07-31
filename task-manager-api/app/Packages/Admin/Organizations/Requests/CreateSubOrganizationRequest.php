<?php

namespace App\Packages\Admin\Organizations\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSubOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
