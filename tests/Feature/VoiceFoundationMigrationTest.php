<?php

use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Enums\VoiceProviderStatus;
use Illuminate\Support\Facades\Schema;

test('voice platform migrations create expected tables', function () {
    expect(Schema::hasTable('voice_providers'))->toBeTrue()
        ->and(Schema::hasTable('voice_calls'))->toBeTrue();
});

test('voice platform tables have expected columns', function () {
    expect(Schema::hasColumns('voice_providers', [
        'slug', 'name', 'api_key', 'webhook_secret', 'priority', 'timeout_seconds', 'retry_count', 'status', 'metadata',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('voice_calls', [
            'uuid', 'voice_provider_id', 'ai_employee_id', 'lead_id', 'external_call_id',
            'direction', 'from_number', 'to_number', 'status', 'duration_seconds', 'transcript', 'summary',
        ]))->toBeTrue();
});

test('voice enums expose expected values', function () {
    expect(VoiceProviderStatus::values())->toBe(['active', 'disabled', 'degraded'])
        ->and(VoiceCallStatus::Queued->value)->toBe('queued')
        ->and(VoiceCallDirection::Outbound->value)->toBe('outbound')
        ->and(VoiceCallStatus::Completed->isTerminal())->toBeTrue()
        ->and(VoiceCallStatus::Ringing->isTerminal())->toBeFalse();
});
