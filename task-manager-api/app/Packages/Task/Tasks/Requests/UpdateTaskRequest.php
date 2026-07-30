<?php

namespace App\Packages\Task\Tasks\Requests;

use App\Packages\Admin\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_id' => 'nullable|uuid|exists:task_statuses,id',
            'priority_id' => 'nullable|uuid|exists:task_priorities,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date_format:Y-m-d\TH:i:s.u\Z,Y-m-d\TH:i:s\Z,Y-m-d H:i:s',
            'visibility' => ['sometimes', 'nullable', 'string', Rule::in(['personal', 'organization'])],
            'organization_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Organization::class, 'id')],
        ];
    }
}
