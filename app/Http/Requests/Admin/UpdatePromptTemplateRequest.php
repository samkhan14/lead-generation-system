<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Models\PromptTemplate;
use Illuminate\Validation\Rule;

class UpdatePromptTemplateRequest extends StorePromptTemplateRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.prompts.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PromptTemplate $template */
        $template = $this->route('promptTemplate');

        return [
            ...parent::rules(),
            'slug' => ['required', 'string', 'max:255', Rule::unique('prompt_templates', 'slug')->ignore($template->id)],
        ];
    }
}
