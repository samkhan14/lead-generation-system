<?php

namespace App\Domains\AI\DataTransferObjects;

use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiEmployee;

readonly class AiRequest
{
    public function __construct(
        public AiEmployee $employee,
        public PromptContext $context,
        public AiRequestType $type = AiRequestType::Chat,
    ) {}
}
