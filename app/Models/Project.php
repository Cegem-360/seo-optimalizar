<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function rankings(): HasManyThrough
    {
        return $this->hasManyThrough(Ranking::class, Keyword::class);
    }

    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }

    public function apiCredentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function pageSpeedResults(): HasMany
    {
        return $this->hasMany(PageSpeedResult::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    #[Scope]
    protected function withKeywordCount(Builder $builder): void
    {
        $builder->withCount('keywords');
    }

    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->whereHas('keywords');
    }
}
