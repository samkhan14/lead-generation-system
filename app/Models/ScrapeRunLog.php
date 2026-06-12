<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapeRunLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scrape_job_id',
        'level',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ScrapeJob::class, 'scrape_job_id');
    }
}
