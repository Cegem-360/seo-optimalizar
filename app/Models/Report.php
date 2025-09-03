<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
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
        'title',
        'type',
        'period_start',
        'period_end',
        'data',
        'file_path',
        'generated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'data' => 'json',
            'generated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    #[Scope]
    protected function byType(Builder $builder, string $type): void
    {
        $builder->where('type', $type);
    }

    #[Scope]
    protected function recent(Builder $builder, int $days = 30): void
    {
        $builder->where('generated_at', '>=', now()->subDays($days));
    }

    #[Scope]
    protected function completed(Builder $builder): void
    {
        $builder->where('status', 'completed');
    }

    #[Scope]
    protected function pending(Builder $builder): void
    {
        $builder->where('status', 'pending');
    }

    protected function formattedPeriod(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function (): string {
            if (! $this->period_start || ! $this->period_end) {
                return 'N/A';
            }

            return $this->period_start->format('M d') . ' - ' . $this->period_end->format('M d, Y');
        });
    }
}
