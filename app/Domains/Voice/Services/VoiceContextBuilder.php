<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Services\ContextBuilder;
use App\Domains\BusinessKnowledge\Services\ServiceKnowledgeFormatter;
use App\Models\Lead;

class VoiceContextBuilder
{
    public function __construct(
        private ContextBuilder $contextBuilder,
        private ServiceKnowledgeFormatter $serviceKnowledgeFormatter,
    ) {}

    /**
     * Retell/Vapi dynamic variables must be string key-value pairs.
     *
     * @return array<string, string>
     */
    public function dynamicVariables(AiEmployee $employee, Lead $lead): array
    {
        $context = $this->contextBuilder->build($employee, $lead);

        $knowledgeVariables = $this->serviceKnowledgeFormatter->formatCatalogForVoice($context->services);

        return array_filter([
            'lead_name' => $lead->full_name ?: 'there',
            'company' => (string) ($lead->company ?: ''),
            'lead_phone' => (string) ($lead->phone ?: ''),
            'lead_email' => (string) ($lead->email ?: ''),
            'lead_website' => (string) ($lead->website ?: ''),
            'employee_name' => $employee->name,
            ...$knowledgeVariables,
        ], fn (string $value) => trim($value) !== '');
    }
}
