<?php

namespace App\Packages\Social\Person\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }
}
