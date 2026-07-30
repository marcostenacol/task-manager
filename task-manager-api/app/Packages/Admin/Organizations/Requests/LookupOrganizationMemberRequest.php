<?php

namespace App\Packages\Admin\Organizations\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupOrganizationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'digits:11'],
            'organization_id' => ['sometimes', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
