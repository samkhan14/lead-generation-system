<?php

namespace App\Domains\BusinessKnowledge\Models;

use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Models\User;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'slug',
    'description',
    'features',
    'benefits',
    'deliverables',
    'pricing_notes',
    'faqs',
    'objections',
    'cross_sell_ids',
    'upsell_ids',
    'tags',
    'status',
    'version',
    'created_by',
    'updated_by',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'benefits' => 'array',
            'deliverables' => 'array',
            'faqs' => 'array',
            'objections' => 'array',
            'cross_sell_ids' => 'array',
            'upsell_ids' => 'array',
            'tags' => 'array',
            'status' => ServiceStatus::class,
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Service $service): void {
            if (empty($service->uuid)) {
                $service->uuid = (string) Str::uuid();
            }

            if (empty($service->slug) && filled($service->name)) {
                $service->slug = static::uniqueSlug(Str::slug($service->name));
            }
        });
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $counter = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ServiceStatus::Active);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }
}
