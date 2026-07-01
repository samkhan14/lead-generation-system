<?php

namespace App\Http\Requests\Admin;

use App\Domains\Voice\Enums\VoiceProviderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoiceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('voice.providers.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->providerRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function providerRules(?int $ignoreId = null): array
    {
        $allowedSlugs = array_keys(config('voice_platform.provider_drivers', []));

        return [
            'slug' => ['required', 'string', 'max:50', Rule::in($allowedSlugs), Rule::unique('voice_providers', 'slug')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'api_key' => [$ignoreId ? 'nullable' : 'required', 'string', 'max:5000'],
            'webhook_secret' => ['nullable', 'string', 'max:5000'],
            'api_base_url' => ['nullable', 'string', 'max:500', 'url'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'retry_count' => ['required', 'integer', 'min:0', 'max:10'],
            'status' => ['required', Rule::enum(VoiceProviderStatus::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
