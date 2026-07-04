<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartBulkLeadVoiceCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('voice.calls.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $eligibleRoles = config('voice_platform.eligible_employee_roles', []);
        $maxBulk = (int) config('voice_platform.bulk.max_leads_per_request', 50);

        return [
            'lead_ids' => ['required', 'array', 'min:1', 'max:'.$maxBulk],
            'lead_ids.*' => ['integer', 'distinct', Rule::exists('leads', 'id')],
            'ai_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('ai_employees', 'id')->where(function ($query) use ($eligibleRoles) {
                    $query->whereIn('role', $eligibleRoles);
                }),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lead_ids.required' => 'Select at least one lead to call.',
            'lead_ids.max' => 'You can queue up to :max leads per bulk action.',
        ];
    }
}
