<?php

namespace App\Models;

use Database\Factories\CompetitorFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, CompetitorRanking> $competitorRankings
 * @property-read int|null $competitor_rankings_count
 * @property-read Project|null $project
 *
 * @method static Builder<static>|Competitor active()
 * @method static CompetitorFactory factory($count = null, $state = [])
 * @method static Builder<static>|Competitor newModelQuery()
 * @method static Builder<static>|Competitor newQuery()
 * @method static Builder<static>|Competitor query()
 *
 * @mixin Model
 */
class Competitor extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Project) {
                $builder->where('project_id', $tenant->id);
            }
        });
    }

    protected $fillable = [
        'project_id',
        'name',
        'url',
        'domain',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function competitorRankings(): HasMany
    {
        return $this->hasMany(CompetitorRanking::class);
    }

    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('is_active', true);
    }
}
