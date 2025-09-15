<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorRanking extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Project) {
                $builder->whereHas('competitor', function (Builder $builder) use ($tenant): void {
                    $builder->where('project_id', $tenant->id);
                });
            }
        });
    }

    protected $fillable = [
        'competitor_id',
        'keyword_id',
        'position',
        'previous_position',
        'url',
        'featured_snippet',
        'serp_features',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'previous_position' => 'integer',
            'featured_snippet' => 'boolean',
            'serp_features' => 'json',
            'checked_at' => 'datetime',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    protected function positionChange(): Attribute
    {
        return Attribute::make(get: function (): ?int {
            if ($this->previous_position === null) {
                return null;
            }

            return $this->previous_position - $this->position;
        });
    }

    #[Scope]
    protected function topTen(Builder $builder): void
    {
        $builder->where('position', '<=', 10);
    }

    #[Scope]
    protected function recentlyChecked(Builder $builder, int $days = 7): void
    {
        $builder->where('checked_at', '>=', now()->subDays($days));
    }
}
