<?php

namespace App\Http\Requests\Admin;

use App\Domains\AI\Models\AiProvider;

class UpdateAiProviderRequest extends StoreAiProviderRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai.providers.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var AiProvider $provider */
        $provider = $this->route('aiProvider');

        return $this->providerRules($provider->id);
    }
}
