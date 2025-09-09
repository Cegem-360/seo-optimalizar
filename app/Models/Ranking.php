<?php

namespace App\Models;

use Database\Factories\RankingFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Keyword|null $keyword
 * @property-read int|null $position_change
 * @property-read string $position_trend
 *
 * @method static Builder<static>|Ranking declined()
 * @method static RankingFactory factory($count = null, $state = [])
 * @method static Builder<static>|Ranking improved()
 * @method static Builder<static>|Ranking newModelQuery()
 * @method static Builder<static>|Ranking newQuery()
 * @method static Builder<static>|Ranking query()
 * @method static Builder<static>|Ranking recentlyChecked(int $days = 7)
 * @method static Builder<static>|Ranking topTen()
 * @method static Builder<static>|Ranking topThree()
 *
 * @mixin Model
 */
class Ranking extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Project) {
                $builder->whereHas('keyword', function (Builder $builder) use ($tenant): void {
                    $builder->where('project_id', $tenant->id);
                });
            }
        });
    }

    protected $fillable = [
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

    protected function positionTrend(): Attribute
    {
        return Attribute::make(get: function (): string {
            $change = $this->position_change;
            if ($change === null) {
                return 'new';
            }

            if ($change > 0) {
                return 'up';
            }

            if ($change < 0) {
                return 'down';
            }

            return 'same';
        });
    }

    #[Scope]
    protected function topTen(Builder $builder): void
    {
        $builder->where('position', '<=', 10);
    }

    #[Scope]
    protected function topThree(Builder $builder): void
    {
        $builder->where('position', '<=', 3);
    }

    #[Scope]
    protected function improved(Builder $builder): void
    {
        $builder->whereNotNull('previous_position')
            ->whereRaw('position < previous_position');
    }

    #[Scope]
    protected function declined(Builder $builder): void
    {
        $builder->whereNotNull('previous_position')
            ->whereRaw('position > previous_position');
    }

    #[Scope]
    protected function recentlyChecked(Builder $builder, int $days = 7): void
    {
        $builder->where('checked_at', '>=', now()->subDays($days));
    }
}
