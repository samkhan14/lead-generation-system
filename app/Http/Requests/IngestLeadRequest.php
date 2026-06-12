<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['nullable', 'string', 'max:255', 'required_without:company'],
            'company' => ['nullable', 'string', 'max:255', 'required_without:business_name'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'scrape_keyword' => ['nullable', 'string', 'max:255'],
            'scrape_city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
