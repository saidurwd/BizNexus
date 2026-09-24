<?php

namespace Modules\Core\Models;

use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer organisation of the platform. It owns legal entities (companies) and users;
 * nothing is shared between tenants. data_region pins the tenant to one regional deployment.
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'data_region',
        'status',
    ];

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The tenant used for existing data and single-organisation installations.
     */
    public static function default(): self
    {
        return static::firstOrCreate(['code' => 'DEFAULT'], ['name' => 'Default', 'status' => 'active']);
    }
}
