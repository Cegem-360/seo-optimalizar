<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'report_date',
        'sessions',
        'active_users',
        'total_users',
        'new_users',
        'bounce_rate',
        'average_session_duration',
        'screen_page_views',
        'conversions',
        'traffic_sources',
        'top_pages',
        'user_demographics',
        'device_data',
        'conversion_data',
        'real_time',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'sessions' => 'integer',
            'active_users' => 'integer',
            'total_users' => 'integer',
            'new_users' => 'integer',
            'bounce_rate' => 'decimal:2',
            'average_session_duration' => 'decimal:2',
            'screen_page_views' => 'integer',
            'conversions' => 'integer',
            'traffic_sources' => 'array',
            'top_pages' => 'array',
            'user_demographics' => 'array',
            'device_data' => 'array',
            'conversion_data' => 'array',
            'real_time' => 'array',
            'raw_data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Helper methods for analytics data
    public function getTopTrafficSource(): ?array
    {
        if (empty($this->traffic_sources)) {
            return null;
        }

        return collect($this->traffic_sources)
            ->sortByDesc('sessions')
            ->first();
    }

    public function getTopPage(): ?array
    {
        if (empty($this->top_pages)) {
            return null;
        }

        return collect($this->top_pages)
            ->sortByDesc('screenPageViews')
            ->first();
    }

    public function getTotalConversions(): int
    {
        if (empty($this->conversion_data)) {
            return 0;
        }

        return collect($this->conversion_data)
            ->sum('conversions');
    }

    public function getMobileTrafficPercentage(): float
    {
        if (empty($this->device_data)) {
            return 0;
        }

        $totalSessions = collect($this->device_data)->sum('sessions');
        $mobileSessions = collect($this->device_data)
            ->where('deviceCategory', 'mobile')
            ->sum('sessions');

        return $totalSessions > 0 ? ($mobileSessions / $totalSessions) * 100 : 0;
    }
}
