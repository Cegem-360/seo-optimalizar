<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SearchConsoleRankingFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchConsoleRanking extends Model
{
    /** @use HasFactory<SearchConsoleRankingFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'keyword_id',
        'query',
        'page',
        'country',
        'device',
        'position',
        'previous_position',
        'position_change',
        'clicks',
        'impressions',
        'ctr',
        'date_from',
        'date_to',
        'days_count',
        'previous_clicks',
        'previous_impressions',
        'previous_ctr',
        'clicks_change_percent',
        'impressions_change_percent',
        'raw_data',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'decimal:2',
            'previous_position' => 'decimal:2',
            'position_change' => 'integer',
            'clicks' => 'integer',
            'impressions' => 'integer',
            'ctr' => 'decimal:4',
            'date_from' => 'date',
            'date_to' => 'date',
            'days_count' => 'integer',
            'previous_clicks' => 'integer',
            'previous_impressions' => 'integer',
            'previous_ctr' => 'decimal:4',
            'clicks_change_percent' => 'decimal:2',
            'impressions_change_percent' => 'decimal:2',
            'raw_data' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    // Attributes
    protected function positionTrend(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->previous_position === null) {
                    return 'new';
                }

                if ($this->position < $this->previous_position) {
                    return 'improved';
                }

                if ($this->position > $this->previous_position) {
                    return 'declined';
                }

                return 'stable';
            }
        );
    }

    protected function clicksTrend(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->previous_clicks === null) {
                    return 'new';
                }

                if ($this->clicks > $this->previous_clicks) {
                    return 'up';
                }

                if ($this->clicks < $this->previous_clicks) {
                    return 'down';
                }

                return 'stable';
            }
        );
    }

    protected function dateRange(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $from = Carbon::parse($this->date_from)->format('M d, Y');
                $to = Carbon::parse($this->date_to)->format('M d, Y');

                if ($from === $to) {
                    return $from;
                }

                return sprintf('%s - %s', $from, $to);
            }
        );
    }

    // Scopes
    protected function scopeDateRange(Builder $builder, $from, $to): Builder
    {
        return $builder->where('date_from', '>=', $from)
            ->where('date_to', '<=', $to);
    }

    #[Scope]
    protected function currentPeriod(Builder $builder): Builder
    {
        return $builder->whereDate('date_to', '>=', Carbon::now()->subDays(30));
    }

    #[Scope]
    protected function topPositions(Builder $builder, int $limit = 10): Builder
    {
        return $builder->where('position', '<=', $limit);
    }

    #[Scope]
    protected function improved(Builder $builder): Builder
    {
        return $builder->whereNotNull('previous_position')
            ->whereColumn('position', '<', 'previous_position');
    }

    #[Scope]
    protected function declined(Builder $builder): Builder
    {
        return $builder->whereNotNull('previous_position')
            ->whereColumn('position', '>', 'previous_position');
    }

    #[Scope]
    protected function withClicks(Builder $builder): Builder
    {
        return $builder->where('clicks', '>', 0);
    }

    #[Scope]
    protected function byDevice(Builder $builder, string $device): Builder
    {
        return $builder->where('device', $device);
    }

    #[Scope]
    protected function byCountry(Builder $builder, string $country): Builder
    {
        return $builder->where('country', $country);
    }

    // Helper methods
    public function isTopThree(): bool
    {
        return $this->position <= 3;
    }

    public function isTopTen(): bool
    {
        return $this->position <= 10;
    }

    public function isFirstPage(): bool
    {
        return $this->position <= 10;
    }

    public function getPositionBadgeColor(): string
    {
        return match (true) {
            $this->position <= 3 => 'success',
            $this->position <= 10 => 'warning',
            $this->position <= 20 => 'info',
            $this->position <= 50 => 'gray',
            default => 'danger',
        };
    }
}
