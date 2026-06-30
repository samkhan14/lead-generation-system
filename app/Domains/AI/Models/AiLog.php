<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiRequestType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'ai_employee_id',
    'ai_provider_id',
    'ai_model_id',
    'lead_id',
    'request_type',
    'prompt_tokens',
    'completion_tokens',
    'total_tokens',
    'cost_usd',
    'latency_ms',
    'status',
    'error_message',
    'request_payload',
    'response_payload',
])]
class AiLog extends Model
{
    protected function casts(): array
    {
        return [
            'request_type' => AiRequestType::class,
            'status' => AiLogStatus::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost_usd' => 'float',
            'latency_ms' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AiLog $log): void {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(AiEmployee::class, 'ai_employee_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
