<?php

namespace App\Models;

use Database\Factories\ApiCredentialFactory;
use App\Models\Project;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Project|null $project
 * @property array<string, mixed> $credentials
 * @method static ApiCredentialFactory factory($count = null, $state = [])
 * @method static Builder<static>|ApiCredential newModelQuery()
 * @method static Builder<static>|ApiCredential newQuery()
 * @method static Builder<static>|ApiCredential query()
 * @mixin Model
 */
class ApiCredential extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant instanceof \App\Models\Project) {
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
