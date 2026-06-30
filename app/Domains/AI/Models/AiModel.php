<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\AiModelStatus;
use Database\Factories\AiModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_provider_id',
    'slug',
    'name',
    'capabilities',
    'max_tokens',
    'input_price_per_1k',
    'output_price_per_1k',
    'status',
])]
class AiModel extends Model
{
    /** @use HasFactory<AiModelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'status' => AiModelStatus::class,
            'max_tokens' => 'integer',
            'input_price_per_1k' => 'float',
            'output_price_per_1k' => 'float',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', AiModelStatus::Active);
    }

    public function supports(string $capability): bool
    {
        return (bool) data_get($this->capabilities, $capability, false);
    }

    protected static function newFactory(): AiModelFactory
    {
        return AiModelFactory::new();
    }
}
