<?php

namespace App\Packages\Admin\Organizations\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('owner_cpf')) {
            $this->merge(['owner_cpf' => removeMask($this->owner_cpf)]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Organization::class, 'id')],
            'owner_cpf' => ['sometimes', 'nullable', 'string', 'size:11'],
        ];
    }
}
