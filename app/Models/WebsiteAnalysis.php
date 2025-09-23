<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteAnalysis extends Model
{
    protected $fillable = [
        'project_id',
        'url',
        'analysis_type',
        'ai_provider',
        'ai_model',
        'request_params',
        'raw_response',
        'overall_score',
        'scores',
        'metadata',
        'status',
        'error_message',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_params' => 'array',
            'scores' => 'array',
            'metadata' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AnalysisSection::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
