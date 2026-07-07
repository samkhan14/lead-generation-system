<?php

namespace App\Http\Requests;

use App\Domains\Crm\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'integer', Rule::exists(Lead::class, 'id')],
            'deal_id' => ['nullable', 'integer', Rule::exists(Deal::class, 'id')],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high'])],
        ];
    }
}
