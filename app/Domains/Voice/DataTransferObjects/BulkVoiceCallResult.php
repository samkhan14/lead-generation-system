<?php

namespace App\Domains\Voice\DataTransferObjects;

readonly class BulkVoiceCallResult
{
    /**
     * @param  list<string>  $queuedCallUuids
     * @param  list<array{lead_id: int, reason: string}>  $skippedDetails
     */
    public function __construct(
        public int $queued,
        public int $skipped,
        public array $queuedCallUuids = [],
        public array $skippedDetails = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'queued' => $this->queued,
            'skipped' => $this->skipped,
            'queued_call_uuids' => $this->queuedCallUuids,
            'skipped_details' => $this->skippedDetails,
        ];
    }
}
