<?php

namespace App\Packages\Task\Tasks\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_id' => 'required|uuid|exists:task_statuses,id',
            'priority_id' => 'required|uuid|exists:task_priorities,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['personal', 'organization'])],
            'organization_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
