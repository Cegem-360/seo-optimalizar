<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCredential extends Model
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
        'service',
        'credentials',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:json',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getCredential(string $key): mixed
    {
        return $this->credentials[$key] ?? null;
    }

    public function setCredential(string $key, mixed $value): void
    {
        $credentials = $this->credentials ?? [];
        $credentials[$key] = $value;
        $this->credentials = $credentials;
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
