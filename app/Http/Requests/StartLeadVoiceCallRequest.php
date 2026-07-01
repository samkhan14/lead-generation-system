<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartLeadVoiceCallRequest extends FormRequest
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

        return [
            'ai_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('ai_employees', 'id')->where(function ($query) use ($eligibleRoles) {
                    $query->whereIn('role', $eligibleRoles);
                }),
            ],
        ];
    }
}
