<?php

namespace App\Packages\Task\Tasks\Requests;

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
        ];
    }
}
