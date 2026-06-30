<?php

namespace App\Models;

use App\Support\LeadIdentifiers;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'assigned_to',
    'created_by',
    'first_name',
    'last_name',
    'email',
    'email_normalized',
    'phone',
    'phone_normalized',
    'website',
    'website_normalized',
    'company',
    'job_title',
    'source',
    'status',
    'notes',
    'metadata',
    'verified_at',
    'last_contacted_at',
])]
class Lead extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'verified_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (Lead $lead): void {
            $lead->email_normalized = LeadIdentifiers::normalizeEmail($lead->email);
            $lead->phone_normalized = LeadIdentifiers::normalizePhone($lead->phone);
            $lead->website_normalized = LeadIdentifiers::normalizeWebsite($lead->website);
        });
    }

    public static function findDuplicate(?string $email, ?string $phone, ?string $website): ?self
    {
        $identifiers = collect([
            'email_normalized' => LeadIdentifiers::normalizeEmail($email),
            'phone_normalized' => LeadIdentifiers::normalizePhone($phone),
            'website_normalized' => LeadIdentifiers::normalizeWebsite($website),
        ])->filter();

        if ($identifiers->isEmpty()) {
            return null;
        }

        return static::query()
            ->where(function (Builder $query) use ($identifiers): void {
                foreach ($identifiers as $column => $value) {
                    $query->orWhere($column, $value);
                }
            })
            ->first();
    }

    public function scopeWithTemperature(Builder $query, string $temperature): Builder
    {
        return $query->whereHas('latestScore', fn (Builder $scoreQuery) => $scoreQuery->where('temperature', $temperature));
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(LeadScore::class);
    }

    public function latestScore(): HasOne
    {
        return $this->hasOne(LeadScore::class)->latestOfMany('calculated_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
