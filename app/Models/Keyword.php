<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keyword extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant) {
                $builder->where('project_id', $tenant->id);
            }
        });
    }

    protected $fillable = [
        'project_id',
        'keyword',
        'category',
        'priority',
        'geo_target',
        'language',
        'search_volume',
        'difficulty_score',
        'intent_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'search_volume' => 'integer',
            'difficulty_score' => 'integer',
            'priority' => 'string',
            'intent_type' => 'string',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function latestRanking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class, 'id', 'keyword_id')
            ->latest('checked_at');
    }

    #[Scope]
    protected function highPriority(Builder $builder): void
    {
        $builder->where('priority', 'high');
    }

    #[Scope]
    protected function byCategory(Builder $builder, string $category): void
    {
        $builder->where('category', $category);
    }

    #[Scope]
    protected function byIntentType(Builder $builder, string $intentType): void
    {
        $builder->where('intent_type', $intentType);
    }
}
