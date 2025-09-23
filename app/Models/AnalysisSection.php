<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisSection extends Model
{
    protected $fillable = [
        'website_analysis_id',
        'section_type',
        'section_name',
        'score',
        'status',
        'findings',
        'recommendations',
        'data',
        'summary',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'recommendations' => 'array',
            'data' => 'array',
        ];
    }

    public function websiteAnalysis(): BelongsTo
    {
        return $this->belongsTo(WebsiteAnalysis::class);
    }

    public function isGood(): bool
    {
        return $this->status === 'good';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
