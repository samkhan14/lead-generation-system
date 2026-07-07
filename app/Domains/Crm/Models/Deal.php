<?php

namespace App\Domains\Crm\Models;

use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Deal extends Model
{
    protected $fillable = [
        'uuid',
        'lead_id',
        'service_id',
        'title',
        'stage',
        'value',
        'currency',
        'probability',
        'expected_close_date',
        'won_at',
        'lost_at',
        'lost_reason',
        'notes',
        'assigned_to',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Deal $deal): void {
            if (empty($deal->uuid)) {
                $deal->uuid = (string) Str::uuid();
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->stage, ['won', 'lost'], true);
    }
}
