<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'latest_project_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->projects;
    }

    public function canAccessTenant(Model $model): bool
    {
        return $this->projects()->whereKey($model)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->latestProject;
    }

    public function latestProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'latest_project_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function getNotificationPreferencesForProject(Project $project): NotificationPreference
    {
        return $this->notificationPreferences()
            ->where('project_id', $project->id)
            ->first() ?? \App\Models\NotificationPreference::query()->make([
                'user_id' => $this->id,
                'project_id' => $project->id,
                'email_ranking_changes' => true,
                'email_top3_achievements' => true,
                'email_first_page_entries' => true,
                'email_significant_drops' => true,
                'email_weekly_summary' => true,
                'app_ranking_changes' => true,
                'app_top3_achievements' => true,
                'app_first_page_entries' => true,
                'app_significant_drops' => true,
                'significant_change_threshold' => 5,
                'only_significant_changes' => false,
            ]);
    }
}
