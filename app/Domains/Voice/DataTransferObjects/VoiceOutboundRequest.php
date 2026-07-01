<?php

namespace App\Domains\Voice\DataTransferObjects;

use App\Domains\AI\Models\AiEmployee;
use App\Models\Lead;

readonly class VoiceOutboundRequest
{
    /**
     * @param  array<string, string>  $dynamicVariables
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public AiEmployee $employee,
        public Lead $lead,
        public string $toNumber,
        public ?string $fromNumber = null,
        public ?string $agentId = null,
        public array $dynamicVariables = [],
        public array $metadata = [],
    ) {}
}
