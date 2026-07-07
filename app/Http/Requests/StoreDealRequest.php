<?php

namespace App\Http\Requests;

use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.deals.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'service_id' => ['nullable', 'integer', Rule::exists(Service::class, 'id')],
            'stage' => ['nullable', 'string', Rule::in(array_keys(config('deals.stages', [])))],
            'value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
