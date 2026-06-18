<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Reddit leads are people/intent posts — they have no business name or
        // company, so identity comes from first_name (username) + reddit_post_id.
        $isReddit = $this->input('source') === 'reddit';

        return [
            'business_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $isReddit && blank($this->input('company')))],
            'company' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $isReddit && blank($this->input('business_name')))],
            'first_name' => ['nullable', 'string', 'max:255', Rule::requiredIf($isReddit)],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'yelp_business_id' => ['nullable', 'string', 'max:255'],
            'yelp_url' => ['nullable', 'string', 'max:500'],
            'osm_id' => ['nullable', 'string', 'max:64'],
            'osm_type' => ['nullable', 'string', 'max:16'],
            'osm_url' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'max:255'],
            'scrape_keyword' => ['nullable', 'string', 'max:255'],
            'scrape_country' => ['nullable', 'string', 'max:255'],
            'scrape_city' => ['nullable', 'string', 'max:255'],
            'scrape_area' => ['nullable', 'string', 'max:255'],
            'scrape_industry' => ['nullable', 'string', 'max:255'],
            'scrape_job_uuid' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
