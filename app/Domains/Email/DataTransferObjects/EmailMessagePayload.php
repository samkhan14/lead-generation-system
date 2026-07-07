<?php

namespace App\Domains\Email\DataTransferObjects;

readonly class EmailMessagePayload
{
    public function __construct(
        public string $toEmail,
        public string $subject,
        public string $htmlBody,
        public ?string $toName = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $replyTo = null,
        public ?string $textBody = null,
    ) {}
}
