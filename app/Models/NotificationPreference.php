<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'email_ranking_changes',
        'email_top3_achievements',
        'email_first_page_entries',
        'email_significant_drops',
        'email_weekly_summary',
        'app_ranking_changes',
        'app_top3_achievements',
        'app_first_page_entries',
        'app_significant_drops',
        'significant_change_threshold',
        'only_significant_changes',
    ];

    protected function casts(): array
    {
        return [
            'email_ranking_changes' => 'boolean',
            'email_top3_achievements' => 'boolean',
            'email_first_page_entries' => 'boolean',
            'email_significant_drops' => 'boolean',
            'email_weekly_summary' => 'boolean',
            'app_ranking_changes' => 'boolean',
            'app_top3_achievements' => 'boolean',
            'app_first_page_entries' => 'boolean',
            'app_significant_drops' => 'boolean',
            'only_significant_changes' => 'boolean',
            'significant_change_threshold' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function shouldReceiveEmail(string $changeType): bool
    {
        return match ($changeType) {
            'top3' => $this->email_top3_achievements,
            'first_page' => $this->email_first_page_entries,
            'dropped_out' => $this->email_significant_drops,
            'significant_improvement' => $this->email_ranking_changes,
            'significant_decline' => $this->email_significant_drops,
            default => $this->email_ranking_changes,
        };
    }

    public function shouldReceiveAppNotification(string $changeType): bool
    {
        return match ($changeType) {
            'top3' => $this->app_top3_achievements,
            'first_page' => $this->app_first_page_entries,
            'dropped_out' => $this->app_significant_drops,
            'significant_improvement' => $this->app_ranking_changes,
            'significant_decline' => $this->app_significant_drops,
            default => $this->app_ranking_changes,
        };
    }
}
