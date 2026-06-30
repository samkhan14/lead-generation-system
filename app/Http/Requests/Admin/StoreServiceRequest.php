<?php

namespace App\Http\Requests\Admin;

use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('services.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->serviceRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function serviceRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('services', 'slug')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:500'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['nullable', 'string', 'max:500'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*' => ['nullable', 'string', 'max:500'],
            'pricing_notes' => ['nullable', 'string'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['required_with:faqs', 'string', 'max:500'],
            'faqs.*.answer' => ['required_with:faqs', 'string', 'max:2000'],
            'objections' => ['nullable', 'array'],
            'objections.*.objection' => ['required_with:objections', 'string', 'max:500'],
            'objections.*.response' => ['required_with:objections', 'string', 'max:2000'],
            'cross_sell_ids' => ['nullable', 'array'],
            'cross_sell_ids.*' => ['integer', Rule::exists('services', 'id')],
            'upsell_ids' => ['nullable', 'array'],
            'upsell_ids.*' => ['integer', Rule::exists('services', 'id')],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(ServiceStatus::class)],
        ];
    }
}
