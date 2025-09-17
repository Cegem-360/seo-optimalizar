<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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
        'ai_discovered',
        'competitor_type',
        'strength_score',
        'relevance_reason',
        'main_advantages',
        'estimated_traffic',
        'content_focus',
        'competitor_strengths',
        'competitor_weaknesses',
        'project_strengths',
        'project_weaknesses',
        'opportunities',
        'threats',
        'action_items',
        'competitive_advantage_score',
        'ai_analysis_summary',
    ];

    protected function casts(): array
    {
        return [
            'has_schema_markup' => 'boolean',
            'has_featured_snippet' => 'boolean',
            'is_mobile_friendly' => 'boolean',
            'has_ssl' => 'boolean',
            'ai_discovered' => 'boolean',
            'headers_structure' => 'array',
            'main_advantages' => 'array',
            'competitor_strengths' => 'array',
            'competitor_weaknesses' => 'array',
            'project_strengths' => 'array',
            'project_weaknesses' => 'array',
            'opportunities' => 'array',
            'threats' => 'array',
            'action_items' => 'array',
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

    protected function positionBadgeColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match (true) {
            $this->position <= 3 => 'success',
            $this->position <= 10 => 'info',
            $this->position <= 20 => 'warning',
            default => 'danger',
        });
    }

    protected function strengthScore(): Attribute
    {
        return Attribute::make(get: function (): float|int {
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
        });
    }

    protected function competitorTypeColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->competitor_type) {
            'direct' => 'danger',
            'indirect' => 'warning',
            'content' => 'info',
            default => 'secondary',
        });
    }

    protected function trafficColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->estimated_traffic) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'success',
            default => 'secondary',
        });
    }

    protected function isStrongCompetitor(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->strength_score >= 7);
    }

    protected function actionItemsByPriority(): Attribute
    {
        return Attribute::make(get: function () {
            /** @var array|null $actionItems */
            $actionItems = $this->action_items;
            // Ha nincs adat vagy nem array, üres tömböt adunk vissza
            if (! is_array($actionItems) || $actionItems === []) {
                return [];
            }

            return (new Collection($actionItems))
                ->sortBy(function ($item): int {
                    if (! is_array($item)) {
                        return 3;
                    }

                    $priorities = ['high' => 1, 'medium' => 2, 'low' => 3];

                    return $priorities[$item['priority'] ?? 'low'] ?? 3;
                })
                ->values()
                ->toArray();
        });
    }
}
