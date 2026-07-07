<?php

namespace App\Http\Requests;

use App\Domains\Crm\Enums\LeadActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leads.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([
                LeadActivityType::Note->value,
                LeadActivityType::Call->value,
                LeadActivityType::Email->value,
                LeadActivityType::Meeting->value,
            ])],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ];
    }
}
