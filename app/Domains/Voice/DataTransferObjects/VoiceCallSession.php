<?php

namespace App\Domains\Voice\DataTransferObjects;

use App\Domains\Voice\Enums\VoiceCallStatus;

readonly class VoiceCallSession
{
    /**
     * @param  array<string, mixed>|null  $raw
     */
    public function __construct(
        public string $externalCallId,
        public VoiceCallStatus $status,
        public ?string $fromNumber = null,
        public ?string $toNumber = null,
        public ?int $durationSeconds = null,
        public ?float $costUsd = null,
        public ?string $transcript = null,
        public ?string $summary = null,
        public ?string $recordingUrl = null,
        public ?string $errorMessage = null,
        public ?array $raw = null,
    ) {}
}
