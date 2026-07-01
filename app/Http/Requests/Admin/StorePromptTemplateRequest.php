<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePromptTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.prompts.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('prompt_templates', 'slug')],
            'category' => ['required', Rule::enum(PromptTemplateCategory::class)],
            'content' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'status' => ['required', Rule::enum(PromptTemplateStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }
}
