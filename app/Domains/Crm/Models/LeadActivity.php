<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LeadActivity extends Model
{
    protected $fillable = [
        'uuid',
        'lead_id',
        'user_id',
        'type',
        'subject',
        'body',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadActivityType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeadActivity $activity): void {
            if (empty($activity->uuid)) {
                $activity->uuid = (string) Str::uuid();
            }

            if ($activity->occurred_at === null) {
                $activity->occurred_at = now();
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
