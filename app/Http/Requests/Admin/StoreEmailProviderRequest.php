<?php

namespace App\Http\Requests\Admin;

use App\Domains\Email\Enums\EmailProviderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.providers.create');
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
        $slug = $this->input('slug');
        $requiresApiKey = ! in_array($slug, ['log', 'smtp'], true);

        return [
            'slug' => ['required', 'string', 'max:50', Rule::in(array_keys(config('email_platform.provider_drivers', []))), Rule::unique('email_providers', 'slug')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'api_key' => [$ignoreId || ! $requiresApiKey ? 'nullable' : 'required', 'string', 'max:5000'],
            'api_base_url' => ['nullable', 'string', 'max:500', 'url'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'retry_count' => ['required', 'integer', 'min:0', 'max:10'],
            'status' => ['required', Rule::enum(EmailProviderStatus::class)],
            'metadata' => ['nullable', 'array'],
            'metadata.default_from_email' => ['nullable', 'email', 'max:255'],
            'metadata.default_from_name' => ['nullable', 'string', 'max:255'],
            'metadata.reply_to' => ['nullable', 'email', 'max:255'],
        ];
    }
}
