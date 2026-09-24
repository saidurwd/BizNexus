<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user_roles')->withPivot(['company_id', 'status', 'valid_from', 'valid_until']);
    }

    public function companyUserRoles(): HasMany
    {
        return $this->hasMany(CompanyUserRole::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
