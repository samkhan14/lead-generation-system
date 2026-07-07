<?php

namespace App\Domains\Email\Models;

use App\Domains\Email\Enums\EmailSendStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'email_campaign_id',
    'email_provider_id',
    'lead_id',
    'initiated_by',
    'to_email',
    'to_name',
    'from_email',
    'from_name',
    'reply_to',
    'subject',
    'html_body',
    'text_body',
    'status',
    'is_test',
    'provider_message_id',
    'error_message',
    'metadata',
    'queued_at',
    'sent_at',
])]
class EmailSend extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EmailSendStatus::class,
            'metadata' => 'array',
            'is_test' => 'boolean',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailSend $send): void {
            if (empty($send->uuid)) {
                $send->uuid = (string) Str::uuid();
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EmailProvider::class, 'email_provider_id');
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
}
