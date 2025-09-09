<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property-read Collection<int, ApiCredential> $apiCredentials
 * @property-read int|null $api_credentials_count
 * @property-read Collection<int, Competitor> $competitors
 * @property-read int|null $competitors_count
 * @property-read Collection<int, Keyword> $keywords
 * @property-read int|null $keywords_count
 * @property-read Collection<int, NotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read Collection<int, PageSpeedResult> $pageSpeedResults
 * @property-read int|null $page_speed_results_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static Builder<static>|Project active()
 * @method static ProjectFactory factory($count = null, $state = [])
 * @method static Builder<static>|Project newModelQuery()
 * @method static Builder<static>|Project newQuery()
 * @method static Builder<static>|Project query()
 * @method static Builder<static>|Project withKeywordCount()
 * @mixin Model
 */
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
