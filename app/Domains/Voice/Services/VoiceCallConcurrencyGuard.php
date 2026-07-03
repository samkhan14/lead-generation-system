<?php

namespace App\Domains\Voice\Services;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\Voice\Models\VoiceCall;
use App\Models\Lead;
use Illuminate\Validation\ValidationException;

class VoiceCallConcurrencyGuard
{
    public function assertCanQueue(AiEmployee $employee, Lead $lead): void
    {
        if (config('voice_platform.concurrency.block_duplicate_lead_employee', true)) {
            $duplicate = VoiceCall::query()
                ->active()
                ->where('ai_employee_id', $employee->id)
                ->where('lead_id', $lead->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'voice_call' => 'This lead already has an active voice call in progress for this AI employee.',
                ]);
            }
        }

        $maxActive = (int) config('voice_platform.concurrency.max_active_calls_per_employee', 50);

        if ($maxActive <= 0) {
            return;
        }

        $activeCount = VoiceCall::query()
            ->active()
            ->where('ai_employee_id', $employee->id)
            ->count();

        if ($activeCount >= $maxActive) {
            throw ValidationException::withMessages([
                'voice_call' => "This AI employee has reached the maximum of {$maxActive} active calls.",
            ]);
        }
    }

    public function activeCountForEmployee(AiEmployee $employee): int
    {
        return VoiceCall::query()
            ->active()
            ->where('ai_employee_id', $employee->id)
            ->count();
    }
}
