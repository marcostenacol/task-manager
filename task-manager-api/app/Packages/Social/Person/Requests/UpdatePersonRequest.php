<?php

namespace App\Packages\Social\Person\Requests;

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge(['cpf' => removeMask($this->cpf)]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'cpf' => [
                'sometimes',
                'nullable',
                'digits:11',
                Rule::unique(User::class, 'cpf')->ignore(userObject()->id),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value && ! isValidCpf($value)) {
                        $fail('CPF inválido.');
                    }
                },
            ],
        ];
    }
}
