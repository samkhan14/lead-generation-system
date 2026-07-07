<?php

namespace App\Http\Requests;

use App\Domains\Crm\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.tasks.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high'])],
            'status' => ['sometimes', 'string', Rule::in(TaskStatus::values())],
        ];
    }
}
