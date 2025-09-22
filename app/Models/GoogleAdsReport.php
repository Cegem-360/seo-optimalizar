<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleAdsReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'report_date',
        'metadata',
        'keyword_data',
        'historical_metrics',
        'bulk_results',
        'statistics',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'metadata' => 'array',
            'keyword_data' => 'array',
            'historical_metrics' => 'array',
            'bulk_results' => 'array',
            'statistics' => 'array',
            'raw_data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
