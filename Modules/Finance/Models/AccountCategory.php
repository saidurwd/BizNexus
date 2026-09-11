<?php


namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountCategory extends Model
{

    protected static function booted()
    {
        static::addGlobalScope(new CompanyScope);
    }
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\Company::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'account_category_id');
    }
}
