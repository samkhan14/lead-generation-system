<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\PromptApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'prompt_template_id',
    'version',
    'content',
    'change_notes',
    'approval_status',
    'approved_by',
    'approved_at',
    'created_by',
])]
class PromptVersion extends Model
{
    protected function casts(): array
    {
        return [
            'approval_status' => PromptApprovalStatus::class,
            'approved_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_template_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
