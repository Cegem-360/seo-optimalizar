<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApiCredentialFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Project|null $project
 * @property array<string, mixed> $credentials
 *
 * @method static ApiCredentialFactory factory($count = null, $state = [])
 * @method static Builder<static>|ApiCredential newModelQuery()
 * @method static Builder<static>|ApiCredential newQuery()
 * @method static Builder<static>|ApiCredential query()
 *
 * @mixin Model
 */
class ApiCredential extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();
            if ($tenant instanceof Project) {
                $builder->where('project_id', $tenant->id);
            }
        });
    }

    protected $fillable = [
        'project_id',
        'service',
        'credentials',
        'service_account_file',
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

    protected function serviceAccountJson(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->service_account_file) {
                $path = storage_path('app/service-accounts/' . $this->service_account_file);
                if (file_exists($path)) {
                    return json_decode(file_get_contents($path), true);
                }
            }
        });
    }

    public function storeServiceAccountFile(string $fileContent): string
    {
        // Delete old file first
        $this->deleteServiceAccountFile();

        $filename = 'project_' . $this->project_id . '_' . $this->service . '_service_account.json';
        $directory = storage_path('app/service-accounts');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($directory . '/' . $filename, $fileContent);

        return $filename;
    }

    public function deleteServiceAccountFile(): void
    {
        if ($this->service_account_file) {
            $path = storage_path('app/service-accounts/' . $this->service_account_file);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
