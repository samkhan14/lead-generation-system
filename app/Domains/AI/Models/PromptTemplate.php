<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'slug',
    'name',
    'category',
    'content',
    'variables',
    'tags',
    'status',
    'created_by',
    'updated_by',
])]
class PromptTemplate extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => PromptTemplateCategory::class,
            'status' => PromptTemplateStatus::class,
            'variables' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PromptTemplate $template): void {
            if (empty($template->uuid)) {
                $template->uuid = (string) Str::uuid();
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PromptVersion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
