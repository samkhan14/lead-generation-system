<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScrapeJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission enforced by controller middleware
    }

    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', Rule::in(config('countries.list'))],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.required' => 'A search keyword is required.',
            'country.required' => 'Country is required.',
        ];
    }
}
