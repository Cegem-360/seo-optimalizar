<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAnalysis extends Model
{
    protected $fillable = [
        'keyword_id',
        'project_id',
        'competition_level',
        'search_intent',
        'dominant_content_types',
        'opportunities',
        'challenges',
        'optimization_tips',
        'summary',
        'position_rating',
        'current_position',
        'target_position',
        'estimated_timeframe',
        'main_competitors',
        'competitor_advantages',
        'improvement_areas',
        'quick_wins',
        'detailed_analysis',
        'raw_response',
        'analysis_source',
    ];

    protected function casts(): array
    {
        return [
            'dominant_content_types' => 'array',
            'opportunities' => 'array',
            'challenges' => 'array',
            'optimization_tips' => 'array',
            'main_competitors' => 'array',
            'competitor_advantages' => 'array',
            'improvement_areas' => 'array',
            'quick_wins' => 'array',
            'raw_response' => 'array',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected function competitionLevelColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->competition_level) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            default => 'gray',
        });
    }

    protected function positionRatingColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->position_rating) {
            'kiváló' => 'success',
            'jó' => 'info',
            'közepes' => 'warning',
            'gyenge' => 'warning',
            'kritikus' => 'danger',
            default => 'gray',
        });
    }
}
