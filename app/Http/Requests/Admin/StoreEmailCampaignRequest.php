<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.campaigns.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'html_body' => ['required', 'string', 'max:500000'],
            'text_body' => ['nullable', 'string', 'max:500000'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'email_provider_id' => ['nullable', 'integer', 'exists:email_providers,id'],
            'ai_enhanced' => ['sometimes', 'boolean'],
        ];
    }
}
