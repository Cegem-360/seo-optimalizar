<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorAnalysis extends Model
{
    protected $fillable = [
        'keyword_id',
        'project_id',
        'competitor_domain',
        'competitor_url',
        'position',
        'domain_authority',
        'page_authority',
        'backlinks_count',
        'content_length',
        'keyword_density',
        'has_schema_markup',
        'has_featured_snippet',
        'page_speed_score',
        'is_mobile_friendly',
        'has_ssl',
        'title_tag',
        'meta_description',
        'headers_structure',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_schema_markup' => 'boolean',
            'has_featured_snippet' => 'boolean',
            'is_mobile_friendly' => 'boolean',
            'has_ssl' => 'boolean',
            'headers_structure' => 'array',
            'analyzed_at' => 'datetime',
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

    public function getPositionBadgeColorAttribute(): string
    {
        return match (true) {
            $this->position <= 3 => 'success',
            $this->position <= 10 => 'info',
            $this->position <= 20 => 'warning',
            default => 'danger',
        };
    }

    public function getStrengthScoreAttribute(): float
    {
        $score = 0;
        $factors = 0;

        if ($this->domain_authority) {
            $score += min($this->domain_authority / 100, 1);
            $factors++;
        }

        if ($this->page_authority) {
            $score += min($this->page_authority / 100, 1);
            $factors++;
        }

        if ($this->page_speed_score) {
            $score += min($this->page_speed_score / 100, 1);
            $factors++;
        }

        if ($this->has_schema_markup) {
            $score += 0.2;
            $factors += 0.2;
        }

        if ($this->is_mobile_friendly) {
            $score += 0.2;
            $factors += 0.2;
        }

        if ($this->has_ssl) {
            $score += 0.1;
            $factors += 0.1;
        }

        return $factors > 0 ? round(($score / $factors) * 100, 2) : 0;
    }
}
