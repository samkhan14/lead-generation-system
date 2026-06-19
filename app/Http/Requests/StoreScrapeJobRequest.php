<?php

namespace App\Http\Requests;

use App\Support\ScraperChannels;
use App\Support\ScraperOptions;
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
        $channel = $this->input('source_channel', config('scraper.default_channel'));

        return [
            'source_channel' => [
                'nullable',
                Rule::in(ScraperChannels::runnableKeys()),
            ],
            'keyword' => [
                'required',
                'string',
                'max:100',
                Rule::in(ScraperOptions::keywordValuesFor($channel)),
            ],
            'country' => [
                Rule::requiredIf(ScraperChannels::requiresLocation($channel)),
                'nullable',
                'string',
                Rule::in(config('countries.list')),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.required' => 'Please select a business type or intent keyword.',
            'keyword.in' => 'Please select a valid option from the list.',
            'country.required' => 'Country is required for this source.',
            'source_channel.in' => 'This lead source is not available yet.',
        ];
    }
}
