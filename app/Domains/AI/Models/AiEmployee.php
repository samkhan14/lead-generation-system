<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Models\User;
use Database\Factories\AiEmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'role',
    'department',
    'description',
    'system_prompt',
    'behavior_prompt',
    'knowledge_sources',
    'allowed_actions',
    'allowed_tools',
    'memory_enabled',
    'context_window',
    'temperature',
    'ai_provider_id',
    'ai_model_id',
    'fallback_provider_id',
    'fallback_model_id',
    'voice_id',
    'language',
    'working_hours',
    'status',
    'created_by',
    'updated_by',
])]
class AiEmployee extends Model
{
    /** @use HasFactory<AiEmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'role' => AiEmployeeRole::class,
            'status' => AiEmployeeStatus::class,
            'knowledge_sources' => 'array',
            'allowed_actions' => 'array',
            'allowed_tools' => 'array',
            'working_hours' => 'array',
            'memory_enabled' => 'boolean',
            'context_window' => 'integer',
            'temperature' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AiEmployee $employee): void {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'fallback_provider_id');
    }

    public function fallbackModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'fallback_model_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AiLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOperational(): bool
    {
        return $this->status === AiEmployeeStatus::Active
            && $this->provider?->isUsable()
            && $this->model !== null;
    }

    protected static function newFactory(): AiEmployeeFactory
    {
        return AiEmployeeFactory::new();
    }
}
