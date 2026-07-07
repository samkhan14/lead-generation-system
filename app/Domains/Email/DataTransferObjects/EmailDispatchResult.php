<?php

namespace App\Domains\Email\DataTransferObjects;

readonly class EmailDispatchResult
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}
