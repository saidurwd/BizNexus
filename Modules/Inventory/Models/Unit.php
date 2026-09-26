<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Concerns\BelongsToCompany;
use Modules\Core\Services\CompanyContextService;

/**
 * A unit of measure products are counted in; decimals is how many places a quantity may have.
 */
class Unit extends Model
{
    use BelongsToCompany;

    protected $table = 'units_of_measure';

    /**
     * @var list<array{code: string, name: string, decimals: int}>
     */
    public const DEFAULTS = [
        ['code' => 'EA', 'name' => 'Each', 'decimals' => 0],
        ['code' => 'BOX', 'name' => 'Box', 'decimals' => 0],
        ['code' => 'KG', 'name' => 'Kilogram', 'decimals' => 3],
        ['code' => 'L', 'name' => 'Litre', 'decimals' => 3],
        ['code' => 'M', 'name' => 'Metre', 'decimals' => 2],
        ['code' => 'HR', 'name' => 'Hour', 'decimals' => 2],
    ];

    protected $fillable = ['company_id', 'code', 'name', 'decimals', 'status'];

    protected $casts = [
        'decimals' => 'integer',
    ];

    /**
     * Give a company the usual units and a main warehouse, leaving any it already has.
     */
    public static function createDefaultsFor(int $companyId): void
    {
        app(CompanyContextService::class)->runAs($companyId, function () use ($companyId) {
            foreach (self::DEFAULTS as $unit) {
                static::firstOrCreate(['company_id' => $companyId, 'code' => $unit['code']], [...$unit, 'status' => 'active']);
            }

            if (! Warehouse::exists()) {
                Warehouse::create(['company_id' => $companyId, 'code' => 'MAIN', 'name' => 'Main warehouse', 'is_default' => true, 'status' => 'active']);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
