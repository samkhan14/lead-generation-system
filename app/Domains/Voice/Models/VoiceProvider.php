<?php

namespace App\Domains\Voice\Models;

use App\Domains\Voice\Enums\VoiceProviderStatus;
use Database\Factories\VoiceProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'api_key',
    'api_base_url',
    'webhook_secret',
    'priority',
    'timeout_seconds',
    'retry_count',
    'status',
    'metadata',
])]
class VoiceProvider extends Model
{
    /** @use HasFactory<VoiceProviderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'status' => VoiceProviderStatus::class,
            'metadata' => 'array',
            'priority' => 'integer',
            'timeout_seconds' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    public function scopeSelectable($query)
    {
        return $query->whereIn('status', [
            VoiceProviderStatus::Active,
            VoiceProviderStatus::Degraded,
        ])->orderBy('priority');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(VoiceCall::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [VoiceProviderStatus::Active, VoiceProviderStatus::Degraded], true)
            && filled($this->api_key);
    }

    public function defaultFromNumber(): ?string
    {
        $number = data_get($this->metadata, 'default_from_number');

        return is_string($number) && $number !== '' ? $number : null;
    }

    public function defaultAgentId(): ?string
    {
        $agentId = data_get($this->metadata, 'agent_id');

        return is_string($agentId) && $agentId !== '' ? $agentId : null;
    }

    public function phoneNumberId(): ?string
    {
        $id = data_get($this->metadata, 'phone_number_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    protected static function newFactory(): VoiceProviderFactory
    {
        return VoiceProviderFactory::new();
    }
}
