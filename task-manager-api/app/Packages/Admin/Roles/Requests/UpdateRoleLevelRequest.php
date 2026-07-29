<?php

namespace App\Packages\Admin\Roles\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => 'required|integer',
        ];
    }
}
