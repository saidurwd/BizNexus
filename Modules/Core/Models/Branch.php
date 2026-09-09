<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'phone',
        'email',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function userBranches(): HasMany
    {
        return $this->hasMany(UserBranch::class, 'branch_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
