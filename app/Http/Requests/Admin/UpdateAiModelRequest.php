<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Models\AiModel;
use Illuminate\Validation\Rule;

class UpdateAiModelRequest extends StoreAiModelRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.models.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var AiModel $model */
        $model = $this->route('aiModel');

        return [
            ...parent::rules(),
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ai_models', 'slug')
                    ->where('ai_provider_id', $this->input('ai_provider_id', $model->ai_provider_id))
                    ->ignore($model->id),
            ],
        ];
    }
}
