<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadIngestionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLeadRequest extends FormRequest
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
        /** @var Lead $lead */
        $lead = $this->route('lead');

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(array_keys(config('lead_pipeline.stages', [])))],
            'lost_reason' => ['nullable', 'string', Rule::in(array_keys(config('lead_pipeline.lost_reasons', [])))],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('status') === 'lost' && blank($this->input('lost_reason'))) {
                $validator->errors()->add('lost_reason', 'Lost reason is required when marking a lead as lost.');
            }

            if (! $this->hasAny(['email', 'phone', 'website'])) {
                return;
            }

            /** @var Lead $lead */
            $lead = $this->route('lead');

            $duplicate = app(LeadIngestionService::class)->findDuplicate(
                app(LeadIngestionService::class)->normalizePayload([
                    'email' => $this->input('email', $lead->email),
                    'phone' => $this->input('phone', $lead->phone),
                    'website' => $this->input('website', $lead->website),
                    'source' => $lead->source,
                ]),
            );

            if ($duplicate && $duplicate->id !== $lead->id) {
                $validator->errors()->add(
                    'duplicate',
                    'A lead with this email, phone, or website already exists.',
                );
            }
        });
    }
}
