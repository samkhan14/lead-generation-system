<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EnhanceEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.campaigns.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:500'],
            'html_body' => ['required', 'string', 'max:500000'],
            'text_body' => ['nullable', 'string', 'max:500000'],
            'tone' => ['nullable', 'string', 'max:100'],
        ];
    }
}
