<?php

namespace App\Domains\AI\Contracts;

use App\Domains\AI\DataTransferObjects\PromptContext;

interface PromptableContextInterface
{
    public function buildInstructions(PromptContext $context): string;

    public function buildUserMessage(PromptContext $context): string;
}
