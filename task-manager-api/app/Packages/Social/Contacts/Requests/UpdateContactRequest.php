<?php

namespace App\Packages\Social\Contacts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contacts' => 'required|array',
            'contacts.*.type' => 'required|string|max:50',
            'contacts.*.value' => 'required|string|max:255',
            'contacts.*.is_primary' => 'sometimes|boolean',
        ];
    }
}
