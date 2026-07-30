<?php

namespace App\Packages\Admin\Organizations\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
