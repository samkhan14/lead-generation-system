<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreKnowledgeBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.knowledge.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('knowledge_bases', 'slug')],
            'category' => ['required', Rule::enum(KnowledgeBaseCategory::class)],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
            'status' => ['required', Rule::enum(KnowledgeBaseStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => Str::slug($this->input('name'))]);
        }
    }
}
