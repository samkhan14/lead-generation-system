<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiEmployeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.employees.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $knowledgeSources = config('ai_platform.knowledge_sources', []);

        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(AiEmployeeRole::class)],
            'department' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'system_prompt' => ['nullable', 'string'],
            'behavior_prompt' => ['nullable', 'string'],
            'knowledge_sources' => ['nullable', 'array'],
            'knowledge_sources.*' => ['string', Rule::in($knowledgeSources)],
            'allowed_actions' => ['nullable', 'array'],
            'allowed_actions.*' => ['string', 'max:100'],
            'allowed_tools' => ['nullable', 'array'],
            'allowed_tools.*' => ['string', 'max:100'],
            'memory_enabled' => ['boolean'],
            'context_window' => ['required', 'integer', 'min:1024', 'max:2000000'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'ai_provider_id' => ['nullable', 'integer', Rule::exists('ai_providers', 'id')],
            'ai_model_id' => ['nullable', 'integer', Rule::exists('ai_models', 'id')],
            'fallback_provider_id' => ['nullable', 'integer', Rule::exists('ai_providers', 'id')],
            'fallback_model_id' => ['nullable', 'integer', Rule::exists('ai_models', 'id')],
            'voice_id' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:10'],
            'working_hours' => ['nullable', 'array'],
            'status' => ['required', Rule::enum(AiEmployeeStatus::class)],
        ];
    }
}
