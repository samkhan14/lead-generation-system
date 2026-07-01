<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Models\KnowledgeBase;
use Illuminate\Validation\Rule;

class UpdateKnowledgeBaseRequest extends StoreKnowledgeBaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.knowledge.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var KnowledgeBase $entry */
        $entry = $this->route('knowledgeBase');

        return [
            ...parent::rules(),
            'slug' => ['required', 'string', 'max:255', Rule::unique('knowledge_bases', 'slug')->ignore($entry->id)],
        ];
    }
}
