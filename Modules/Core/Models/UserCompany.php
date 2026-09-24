<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;

class UserCompany extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'is_default',
        'all_branches',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (UserCompany $access) {
            $userTenantId = User::whereKey($access->user_id)->value('tenant_id');
            $companyTenantId = Company::whereKey($access->company_id)->value('tenant_id');

            if ((int) $userTenantId !== (int) $companyTenantId) {
                throw new UnauthorizedCompanyAccessException((int) $access->company_id, (int) $access->user_id);
            }
        });
    }

    protected $casts = [
        'is_default' => 'boolean',
        'all_branches' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
