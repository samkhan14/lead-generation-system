<?php

namespace App\Domains\Voice\Jobs;

use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Services\VoiceGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class InitiateVoiceCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public int $timeout = 120;

    public function __construct(public VoiceCall $voiceCall)
    {
        $this->onQueue(config('voice_platform.queue', 'voice'));
    }

    public function handle(VoiceGateway $voiceGateway): void
    {
        $call = $this->voiceCall->fresh(['employee', 'lead', 'provider']);

        if ($call === null || $call->isTerminal()) {
            return;
        }

        $voiceGateway->executePending($call);
    }

    public function failed(?Throwable $exception): void
    {
        $call = $this->voiceCall->fresh();

        if ($call === null || $call->isTerminal()) {
            return;
        }

        $call->update([
            'status' => \App\Domains\Voice\Enums\VoiceCallStatus::Failed,
            'error_message' => $exception?->getMessage() ?? 'Voice call job failed after retries.',
            'ended_at' => now(),
        ]);
    }
}
