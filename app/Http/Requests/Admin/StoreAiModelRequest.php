<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Enums\AiModelStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.models.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ai_provider_id' => ['required', 'integer', Rule::exists('ai_providers', 'id')],
            'slug' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['nullable', 'array'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'input_price_per_1k' => ['nullable', 'numeric', 'min:0'],
            'output_price_per_1k' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(AiModelStatus::class)],
        ];
    }
}
