<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSpeedAnalysis extends Model
{
    protected $fillable = [
        'project_id',
        'keyword_id',
        'tested_url',
        'device_type',
        'lcp',
        'fid',
        'cls',
        'fcp',
        'inp',
        'ttfb',
        'performance_score',
        'accessibility_score',
        'best_practices_score',
        'seo_score',
        'total_page_size',
        'total_requests',
        'load_time',
        'resource_breakdown',
        'third_party_resources',
        'opportunities',
        'diagnostics',
        'images_count',
        'unoptimized_images',
        'images_without_alt',
        'render_blocking_resources',
        'unused_css_bytes',
        'unused_js_bytes',
        'analysis_source',
        'analyzed_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'resource_breakdown' => 'array',
            'third_party_resources' => 'array',
            'opportunities' => 'array',
            'diagnostics' => 'array',
            'raw_response' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    protected function performanceColor(): Attribute
    {
        return Attribute::make(get: fn (): string => match (true) {
            $this->performance_score >= 90 => 'success',
            $this->performance_score >= 50 => 'warning',
            default => 'danger',
        });
    }

    protected function coreWebVitalsStatus(): Attribute
    {
        return Attribute::make(get: function (): string {
            $passing = 0;
            $metrics = 0;
            // LCP: Good < 2.5s, Needs Improvement < 4s
            if ($this->lcp !== null) {
                $metrics++;
                if ($this->lcp <= 2.5) {
                    $passing++;
                }
            }

            // FID: Good < 100ms, Needs Improvement < 300ms
            if ($this->fid !== null) {
                $metrics++;
                if ($this->fid <= 100) {
                    $passing++;
                }
            }

            // CLS: Good < 0.1, Needs Improvement < 0.25
            if ($this->cls !== null) {
                $metrics++;
                if ($this->cls <= 0.1) {
                    $passing++;
                }
            }

            if ($metrics === 0) {
                return 'no-data';
            }

            return match (true) {
                $passing === $metrics => 'passing',
                $passing > 0 => 'partial',
                default => 'failing',
            };
        });
    }

    protected function formattedPageSize(): Attribute
    {
        return Attribute::make(get: function (): string {
            if (! $this->total_page_size) {
                return 'N/A';
            }

            $bytes = $this->total_page_size;
            $units = ['B', 'KB', 'MB', 'GB'];
            $unitIndex = 0;
            while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
                $bytes /= 1024;
                $unitIndex++;
            }

            return round($bytes, 2) . ' ' . $units[$unitIndex];
        });
    }
}
