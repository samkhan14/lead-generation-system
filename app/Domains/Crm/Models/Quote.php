<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Quote extends Model
{
    protected $fillable = [
        'uuid',
        'lead_id',
        'deal_id',
        'title',
        'subject',
        'html_body',
        'text_body',
        'service_ids',
        'status',
        'sent_at',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'service_ids' => 'array',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            if (empty($quote->uuid)) {
                $quote->uuid = (string) Str::uuid();
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
