<?php

namespace App\Packages\Social\Contacts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|max:50',
            'value' => 'required|string|max:255',
            'is_primary' => 'sometimes|boolean',
        ];
    }
}
