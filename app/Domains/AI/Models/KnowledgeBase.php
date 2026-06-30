<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'slug',
    'name',
    'category',
    'content',
    'metadata',
    'tags',
    'status',
    'created_by',
    'updated_by',
])]
class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => KnowledgeBaseCategory::class,
            'status' => KnowledgeBaseStatus::class,
            'metadata' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgeBase $entry): void {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', KnowledgeBaseStatus::Active);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
