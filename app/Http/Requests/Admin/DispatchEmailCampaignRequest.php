<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DispatchEmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('email.campaigns.send');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_ids' => ['nullable', 'array', 'max:500'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
        ];
    }
}
