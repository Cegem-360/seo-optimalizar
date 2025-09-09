<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSpeedResult extends Model
{
    protected $fillable = [
        'project_id',
        'url',
        'strategy',
        'performance_score',
        'accessibility_score',
        'best_practices_score',
        'seo_score',
        'lcp_value',
        'lcp_display',
        'lcp_score',
        'fcp_value',
        'fcp_display',
        'fcp_score',
        'cls_value',
        'cls_display',
        'cls_score',
        'speed_index_value',
        'speed_index_display',
        'speed_index_score',
        'raw_data',
        'analyzed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function overallScore(): Attribute
    {
        return Attribute::make(get: function (): ?float {
            $scores = array_filter([
                $this->performance_score,
                $this->accessibility_score,
                $this->best_practices_score,
                $this->seo_score,
            ]);

            return $scores === [] ? null : round(array_sum($scores) / count($scores), 1);
        });
    }

    protected function performanceGrade(): Attribute
    {
        return Attribute::make(get: fn (): string => match (true) {
            $this->performance_score >= 90 => 'excellent',
            $this->performance_score >= 50 => 'needs-improvement',
            default => 'poor',
        });
    }

    protected function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    protected function scopeStrategy($query, string $strategy)
    {
        return $query->where('strategy', $strategy);
    }

    protected function scopeRecent($query, int $days = 30)
    {
        return $query->where('analyzed_at', '>=', now()->subDays($days));
    }

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'analyzed_at' => 'datetime',
            'lcp_value' => 'decimal:2',
            'lcp_score' => 'decimal:2',
            'fcp_value' => 'decimal:2',
            'fcp_score' => 'decimal:2',
            'cls_value' => 'decimal:3',
            'cls_score' => 'decimal:2',
            'speed_index_value' => 'decimal:2',
            'speed_index_score' => 'decimal:2',
        ];
    }
}
