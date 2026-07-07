<?php

namespace App\Http\Requests;

use App\Domains\BusinessKnowledge\Models\Service;
use App\Domains\Crm\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crm.quotes.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', Rule::exists(Service::class, 'id')],
            'deal_id' => ['nullable', 'integer', Rule::exists(Deal::class, 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
