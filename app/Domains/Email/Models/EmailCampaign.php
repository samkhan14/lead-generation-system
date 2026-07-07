<?php

namespace App\Domains\Email\Models;

use App\Domains\Email\Enums\EmailCampaignStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'subject',
    'html_body',
    'text_body',
    'from_name',
    'from_email',
    'reply_to',
    'status',
    'email_provider_id',
    'created_by',
    'updated_by',
    'ai_enhanced',
    'metadata',
    'scheduled_at',
    'sent_at',
])]
class EmailCampaign extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EmailCampaignStatus::class,
            'metadata' => 'array',
            'ai_enhanced' => 'boolean',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailCampaign $campaign): void {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class, 'email_provider_id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
