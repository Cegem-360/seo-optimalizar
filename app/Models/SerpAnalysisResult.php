<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerpAnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'keyword_id',
        'search_id',
        'organic_results',
        'serp_metrics',
        'analysis_data',
        'ai_analysis',
    ];

    protected function casts(): array
    {
        return [
            'organic_results' => 'array',
            'serp_metrics' => 'array',
            'analysis_data' => 'array',
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
}
