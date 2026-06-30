<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\AiProviderStatus;
use Database\Factories\AiProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'api_key',
    'api_base_url',
    'priority',
    'rate_limit_rpm',
    'timeout_seconds',
    'retry_count',
    'status',
    'metadata',
])]
class AiProvider extends Model
{
    /** @use HasFactory<AiProviderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'status' => AiProviderStatus::class,
            'metadata' => 'array',
            'priority' => 'integer',
            'rate_limit_rpm' => 'integer',
            'timeout_seconds' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    public function scopeSelectable($query)
    {
        return $query->whereIn('status', [
            AiProviderStatus::Active,
            AiProviderStatus::Degraded,
        ])->orderBy('priority');
    }

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [AiProviderStatus::Active, AiProviderStatus::Degraded], true)
            && filled($this->api_key);
    }

    protected static function newFactory(): AiProviderFactory
    {
        return AiProviderFactory::new();
    }
}
