<?php

namespace App\Domains\Voice\Models;

use App\Domains\AI\Models\AiEmployee;
use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Models\Lead;
use App\Models\User;
use Database\Factories\VoiceCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'voice_provider_id',
    'ai_employee_id',
    'lead_id',
    'initiated_by',
    'external_call_id',
    'direction',
    'from_number',
    'to_number',
    'status',
    'duration_seconds',
    'cost_usd',
    'transcript',
    'summary',
    'recording_url',
    'metadata',
    'error_message',
    'started_at',
    'ended_at',
])]
class VoiceCall extends Model
{
    /** @use HasFactory<VoiceCallFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'direction' => VoiceCallDirection::class,
            'status' => VoiceCallStatus::class,
            'metadata' => 'array',
            'duration_seconds' => 'integer',
            'cost_usd' => 'float',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VoiceCall $call): void {
            if (empty($call->uuid)) {
                $call->uuid = (string) Str::uuid();
            }
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(VoiceProvider::class, 'voice_provider_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(AiEmployee::class, 'ai_employee_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    protected static function newFactory(): VoiceCallFactory
    {
        return VoiceCallFactory::new();
    }
}
