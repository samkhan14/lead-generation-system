<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Services\ContextBuilder;
use App\Models\Lead;

class VoiceContextBuilder
{
    public function __construct(
        private ContextBuilder $contextBuilder,
    ) {}

    /**
     * Retell/Vapi dynamic variables must be string key-value pairs.
     *
     * @return array<string, string>
     */
    public function dynamicVariables(AiEmployee $employee, Lead $lead): array
    {
        $context = $this->contextBuilder->build($employee, $lead);

        $services = collect($context->services)
            ->take(5)
            ->map(fn ($service) => is_object($service) ? ($service->name ?? null) : ($service['name'] ?? null))
            ->filter()
            ->implode(', ');

        return array_filter([
            'lead_name' => $lead->full_name ?: 'there',
            'company' => (string) ($lead->company ?: ''),
            'lead_phone' => (string) ($lead->phone ?: ''),
            'lead_email' => (string) ($lead->email ?: ''),
            'lead_website' => (string) ($lead->website ?: ''),
            'employee_name' => $employee->name,
            'services_summary' => $services,
        ], fn (string $value) => $value !== '');
    }
}
