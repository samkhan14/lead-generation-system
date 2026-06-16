<?php

namespace App\Models;

use App\Enums\ScrapeJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ScrapeJob extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'source_channel',
        'is_watch',
        'watch_interval_hours',
        'last_dispatched_at',
        'keyword',
        'industry',
        'country',
        'city',
        'area',
        'status',
        'max_results',
        'scraper_used',
        'started_at',
        'completed_at',
        'total_found',
        'created_count',
        'duplicate_count',
        'failed_count',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'status' => ScrapeJobStatus::class,
        'is_watch' => 'boolean',
        'watch_interval_hours' => 'integer',
        'last_dispatched_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'max_results' => 'integer',
        'total_found' => 'integer',
        'created_count' => 'integer',
        'duplicate_count' => 'integer',
        'failed_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ScrapeJob $job) {
            $job->uuid ??= Str::uuid()->toString();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ScrapeRunLog::class);
    }

    public function scopeWatches($query)
    {
        return $query->where('is_watch', true);
    }

    public function isWatchDue(): bool
    {
        if (! $this->is_watch) {
            return false;
        }

        if ($this->last_dispatched_at === null) {
            return true;
        }

        $interval = $this->watch_interval_hours ?: (int) config('reddit.watch_interval_hours', 12);

        return $this->last_dispatched_at->lte(now()->subHours($interval));
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return (int) $this->started_at->diffInSeconds($this->completed_at);
        }

        return null;
    }

    public function successRatio(): float
    {
        $total = $this->total_found;

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->created_count / $total) * 100, 1);
    }

    public function searchLabel(): string
    {
        $parts = array_filter([$this->keyword, $this->area, $this->city, $this->country]);

        return implode(', ', $parts);
    }
}
