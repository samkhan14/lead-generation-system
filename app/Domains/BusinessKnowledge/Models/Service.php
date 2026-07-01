<?php

namespace App\Domains\BusinessKnowledge\Models;

use App\Domains\BusinessKnowledge\Enums\ServiceComplexity;
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
    'short_description',
    'description',
    'detailed_description',
    'target_audience',
    'ideal_customer_profile',
    'problems_solved',
    'features',
    'benefits',
    'deliverables',
    'pricing_notes',
    'faqs',
    'objections',
    'discovery_questions',
    'quotation_requirements',
    'cross_sell_ids',
    'upsell_ids',
    'related_service_ids',
    'tags',
    'technologies',
    'complexity_level',
    'typical_timeline',
    'sort_order',
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
            'target_audience' => 'array',
            'problems_solved' => 'array',
            'features' => 'array',
            'benefits' => 'array',
            'deliverables' => 'array',
            'faqs' => 'array',
            'objections' => 'array',
            'discovery_questions' => 'array',
            'quotation_requirements' => 'array',
            'cross_sell_ids' => 'array',
            'upsell_ids' => 'array',
            'related_service_ids' => 'array',
            'tags' => 'array',
            'technologies' => 'array',
            'complexity_level' => ServiceComplexity::class,
            'sort_order' => 'integer',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
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
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('detailed_description', 'like', "%{$term}%");
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
