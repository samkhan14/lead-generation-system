<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly Lead $lead) {}

    public function handle(LeadVerificationService $verificationService): void
    {
        if (! $verificationService->shouldVerify($this->lead)) {
            return;
        }

        try {
            $verificationService->verify($this->lead->fresh());
        } catch (\Throwable $e) {
            Log::warning('VerifyLeadJob failed', [
                'lead_id' => $this->lead->id,
                'company' => $this->lead->company,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
