<?php

namespace App\Domains\Email\Models;

use App\Domains\Email\Enums\EmailProviderStatus;
use Database\Factories\EmailProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'slug',
    'name',
    'api_key',
    'api_base_url',
    'priority',
    'timeout_seconds',
    'retry_count',
    'status',
    'metadata',
])]
class EmailProvider extends Model
{
    /** @use HasFactory<EmailProviderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'status' => EmailProviderStatus::class,
            'metadata' => 'array',
            'priority' => 'integer',
            'timeout_seconds' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailProvider $provider): void {
            if (empty($provider->uuid)) {
                $provider->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopeSelectable($query)
    {
        return $query->whereIn('status', [
            EmailProviderStatus::Active,
            EmailProviderStatus::Degraded,
        ])->orderBy('priority');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class);
    }

    public function isUsable(): bool
    {
        if (! in_array($this->status, [EmailProviderStatus::Active, EmailProviderStatus::Degraded], true)) {
            return false;
        }

        if ($this->slug === 'log') {
            return true;
        }

        if ($this->slug === 'smtp') {
            return filled($this->defaultFromEmail());
        }

        return filled($this->api_key);
    }

    public function defaultFromEmail(): ?string
    {
        $email = data_get($this->metadata, 'default_from_email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function defaultFromName(): ?string
    {
        $name = data_get($this->metadata, 'default_from_name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function defaultReplyTo(): ?string
    {
        $replyTo = data_get($this->metadata, 'reply_to');

        return is_string($replyTo) && $replyTo !== '' ? $replyTo : null;
    }

    protected static function newFactory(): EmailProviderFactory
    {
        return EmailProviderFactory::new();
    }
}
